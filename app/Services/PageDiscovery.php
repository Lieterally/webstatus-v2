<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Suggests a website's main pages for monitoring, on request from the site form.
 *
 * 1. Links in the homepage's navigation (<nav>, <header>, role="navigation"): the site's own list
 *    of main pages. One request.
 * 2. If that finds fewer than MIN_FROM_MENU pages (for example a menu built by JavaScript), the
 *    sitemap: robots.txt "Sitemap:" lines or common locations, preferring the "pages" part of a
 *    sitemap index, keeping only short paths (one or two segments).
 *
 * Results are same-site paths only, without query strings, files or admin/login pages, grouped by
 * first path segment. The homepage and one page per section are suggested, up to MAX_SUGGESTED.
 * It never runs during monitoring cycles.
 */
class PageDiscovery
{
    public const MAX_CANDIDATES = 50;

    public const MAX_SUGGESTED = 8;

    public const MIN_FROM_MENU = 3;

    public const TIMEOUT_SECONDS = 8;

    public const MAX_BYTES = 2_000_000;

    /** Why the last fetch failed: an HTTP status, or null when there was no answer. */
    private ?int $lastStatus = null;

    private const SKIP_SEGMENTS = [
        'login', 'logout', 'signin', 'sign-in', 'register', 'wp-admin', 'wp-login.php', 'admin',
        'feed', 'rss', 'cart', 'checkout', 'search', 'cdn-cgi', 'xmlrpc.php',
        // language switchers: same pages in another language, not separate pages to monitor
        'language', 'lang', 'locale', 'set-locale',
    ];

    private const FILE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png',
        'gif', 'webp', 'svg', 'mp4', 'mp3', 'xml', 'json', 'css', 'js', 'ico',
    ];

    /**
     * @return array{source: string, pages: array<int, array{path: string, label: string, section: string}>, suggested: array<int, string>, message: ?string}
     */
    public function discover(string $baseUrl): array
    {
        $base = $this->parseBase($baseUrl);
        if (! $base) {
            return $this->result('none', [], 'Enter a base URL that starts with http:// or https://.');
        }

        $html = $this->fetch($base['origin'] . '/');
        if ($html === null) {
            $message = $this->lastStatus
                ? "{$base['origin']} answered with an error (HTTP {$this->lastStatus}). It may be down: try again later, or add pages by hand."
                : "Couldn't reach {$base['origin']}. Check the base URL, or add pages by hand.";

            return $this->result('none', [], $message);
        }

        $pages = $this->fromMenu($html, $base);
        $source = 'menu';

        if (count($pages) < self::MIN_FROM_MENU) {
            $fromSitemap = $this->fromSitemap($base);
            if (count($fromSitemap) > count($pages)) {
                $pages = $this->merge($pages, $fromSitemap);
                $source = 'sitemap';
            }
        }

        // The homepage always leads the list
        $pages = $this->merge([['path' => '/', 'label' => 'Homepage']], $pages);

        $message = count($pages) <= 1
            ? 'Only the homepage was found. The menu may be built by JavaScript and there is no sitemap; add other pages by hand.'
            : null;

        return $this->result($source, $pages, $message);
    }

    private function result(string $source, array $pages, ?string $message): array
    {
        $pages = array_slice($pages, 0, self::MAX_CANDIDATES);
        $pages = array_map(fn ($p) => $p + ['section' => $this->section($p['path'])], $pages);

        // Suggest the homepage and the first (shortest) page of each section
        $suggested = [];
        $seenSections = [];
        $bySection = $pages;
        usort($bySection, fn ($a, $b) => substr_count($a['path'], '/') <=> substr_count($b['path'], '/'));
        foreach ($bySection as $page) {
            if (count($suggested) >= self::MAX_SUGGESTED) {
                break;
            }
            if (! isset($seenSections[$page['section']])) {
                $seenSections[$page['section']] = true;
                $suggested[] = $page['path'];
            }
        }

        return ['source' => $source, 'pages' => array_values($pages), 'suggested' => $suggested, 'message' => $message];
    }

    private function parseBase(string $url): ?array
    {
        $parts = parse_url(trim($url));
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }
        $host = strtolower($parts['host']);
        $origin = strtolower($parts['scheme']) . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');

        return ['origin' => $origin, 'host' => $host, 'bareHost' => preg_replace('/^www\./', '', $host)];
    }

    private function fetch(string $url): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'Webstatus page discovery', 'Accept' => 'text/html,application/xml;q=0.9,*/*;q=0.8'])
                ->connectTimeout(self::TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                // Same as the health checker: certificate problems shouldn't hide the pages
                ->withOptions(['allow_redirects' => ['max' => 3, 'protocols' => ['http', 'https']], 'verify' => false])
                ->get($url);
        } catch (Throwable) {
            $this->lastStatus = null;

            return null;
        }

        if (! $response->successful()) {
            $this->lastStatus = $response->status();

            return null;
        }

        return substr($response->body(), 0, self::MAX_BYTES);
    }

    /** Links inside the page's navigation areas, in menu order. */
    private function fromMenu(string $html, array $base): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//nav//a[@href] | //header//a[@href] | //*[@role="navigation"]//a[@href]');

        $pages = [];
        foreach ($links ?: [] as $link) {
            if ($link->hasAttribute('hreflang')) {
                continue; // language switcher
            }
            $path = $this->normalise($link->getAttribute('href'), $base);
            if ($path === null || $path === '/') {
                continue;
            }
            $label = trim(preg_replace('/\s+/', ' ', $link->textContent));
            $pages[] = ['path' => $path, 'label' => mb_substr($label !== '' ? $label : $path, 0, 80)];
        }

        return $this->merge([], $pages);
    }

    /** Short paths from the sitemap, preferring the "pages" part of a sitemap index. */
    private function fromSitemap(array $base): array
    {
        $candidates = [];
        $robots = $this->fetch($base['origin'] . '/robots.txt');
        if ($robots !== null && preg_match_all('/^\s*sitemap:\s*(\S+)/im', $robots, $m)) {
            $candidates = $m[1];
        }
        $candidates = array_merge($candidates, array_map(fn ($p) => $base['origin'] . $p, ['/sitemap.xml', '/sitemap_index.xml', '/wp-sitemap.xml']));

        foreach (array_unique($candidates) as $sitemapUrl) {
            $urls = $this->readSitemap($sitemapUrl, $base);
            if ($urls) {
                return $urls;
            }
        }

        return [];
    }

    private function readSitemap(string $url, array $base, int $depth = 0): array
    {
        if ($this->normalise($url, $base, allowFiles: true) === null) {
            return []; // only this website's own sitemaps
        }

        $xml = $this->fetch($url);
        if ($xml === null) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $doc) {
            return [];
        }

        $locs = [];
        foreach ($doc->xpath('//*[local-name()="loc"]') ?: [] as $loc) {
            $locs[] = trim((string) $loc);
        }

        // A sitemap index lists other sitemaps: read the one for pages, not posts, if there is one
        if ($doc->getName() === 'sitemapindex') {
            if ($depth > 0) {
                return [];
            }
            usort($locs, fn ($a, $b) => (int) ! preg_match('/page/i', $a) <=> (int) ! preg_match('/page/i', $b));
            foreach (array_slice($locs, 0, 3) as $child) {
                $urls = $this->readSitemap($child, $base, $depth + 1);
                if ($urls) {
                    return $urls;
                }
            }

            return [];
        }

        $pages = [];
        foreach ($locs as $loc) {
            $path = $this->normalise($loc, $base);
            if ($path === null || $path === '/' || substr_count(trim($path, '/'), '/') > 1) {
                continue; // main pages only: one or two path segments
            }
            $pages[] = ['path' => $path, 'label' => $this->labelFromPath($path)];
        }

        usort($pages, fn ($a, $b) => [substr_count($a['path'], '/'), strlen($a['path'])] <=> [substr_count($b['path'], '/'), strlen($b['path'])]);

        return $this->merge([], $pages);
    }

    /** Same-site path without query or fragment, or null when the link should be skipped. */
    private function normalise(string $href, array $base, bool $allowFiles = false): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel|javascript|data):/i', $href)) {
            return null;
        }

        if (str_starts_with($href, '//')) {
            $href = 'https:' . $href;
        }
        $parts = parse_url($href);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['host'])) {
            $host = preg_replace('/^www\./', '', strtolower($parts['host']));
            if ($host !== $base['bareHost']) {
                return null; // another website
            }
        }

        $path = $parts['path'] ?? '/';
        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $path = preg_replace('#/+#', '/', $path);
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $segments = array_values(array_filter(explode('/', strtolower($path))));
        if (array_intersect($segments, self::SKIP_SEGMENTS)) {
            return null;
        }
        // "/id", "/en", "/en-us": the same site in another language, not a separate page
        if (count($segments) === 1 && preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $segments[0])) {
            return null;
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! $allowFiles && $extension !== '' && in_array($extension, self::FILE_EXTENSIONS, true)) {
            return null;
        }

        return mb_substr($path, 0, 255);
    }

    private function merge(array $first, array $second): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge($first, $second) as $page) {
            $key = strtolower($page['path']);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = ['path' => $page['path'], 'label' => $page['label']];
            }
        }

        return $out;
    }

    private function section(string $path): string
    {
        $first = explode('/', trim($path, '/'))[0] ?? '';

        return $first === '' ? 'Homepage' : ucfirst(str_replace(['-', '_'], ' ', urldecode($first)));
    }

    private function labelFromPath(string $path): string
    {
        $last = basename($path);

        return ucfirst(str_replace(['-', '_'], ' ', urldecode($last))) ?: $path;
    }
}
