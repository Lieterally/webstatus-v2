<?php

use App\Models\User;
use App\Services\PageDiscovery;
use Illuminate\Support\Facades\Http;

function discover(string $url): array
{
    return app(PageDiscovery::class)->discover($url);
}

$menuHtml = <<<'HTML'
<html><body>
<header><a href="/">Home</a></header>
<nav>
  <a href="/profil">Profil</a>
  <a href="https://www.example.ac.id/akademik/">Akademik</a>
  <a href="/akademik/jadwal?semester=1#top">Jadwal</a>
  <a href="/berita">Berita</a>
  <a href="/login">Log in</a>
  <a href="/files/brosur.pdf">Brosur</a>
  <a href="https://other.example.com/x">Partner</a>
  <a href="mailto:info@example.ac.id">Email</a>
  <a href="#main">Skip</a>
  <a href="/language/en">English</a>
  <a href="/en/" hreflang="en">EN</a>
  <a href="/id">ID</a>
</nav>
<main><a href="/berita/2026/11/13/long-article">Not in the menu</a></main>
</body></html>
HTML;

it('reads the main pages from the homepage menu and filters the rest', function () use ($menuHtml) {
    Http::fake(['https://example.ac.id/' => Http::response($menuHtml)]);

    $result = discover('https://example.ac.id');

    expect($result['source'])->toBe('menu')
        ->and(collect($result['pages'])->pluck('path')->all())->toBe(['/', '/profil', '/akademik', '/akademik/jadwal', '/berita'])
        ->and($result['pages'][1]['label'])->toBe('Profil')
        ->and($result['message'])->toBeNull();
});

it('suggests the homepage and one page per section', function () use ($menuHtml) {
    Http::fake(['https://example.ac.id/' => Http::response($menuHtml)]);

    expect(discover('https://example.ac.id')['suggested'])->toBe(['/', '/profil', '/akademik', '/berita']);
});

it('falls back to the pages part of the sitemap when the menu has too few links', function () {
    Http::fake([
        'https://example.ac.id/' => Http::response('<html><body><nav><a href="/">Home</a></nav></body></html>'),
        'https://example.ac.id/robots.txt' => Http::response("User-agent: *\nSitemap: https://example.ac.id/sitemap_index.xml"),
        'https://example.ac.id/sitemap_index.xml' => Http::response(
            '<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . '<sitemap><loc>https://example.ac.id/post-sitemap.xml</loc></sitemap>'
            . '<sitemap><loc>https://example.ac.id/page-sitemap.xml</loc></sitemap></sitemapindex>'
        ),
        'https://example.ac.id/page-sitemap.xml' => Http::response(
            '<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . '<url><loc>https://example.ac.id/</loc></url>'
            . '<url><loc>https://example.ac.id/kontak/</loc></url>'
            . '<url><loc>https://example.ac.id/pmb/jalur-masuk/</loc></url>'
            . '<url><loc>https://example.ac.id/a/b/c/too-deep/</loc></url></urlset>'
        ),
        '*' => Http::response('', 404),
    ]);

    $result = discover('https://example.ac.id');

    expect($result['source'])->toBe('sitemap')
        ->and(collect($result['pages'])->pluck('path')->all())->toBe(['/', '/kontak', '/pmb/jalur-masuk'])
        ->and($result['pages'][1]['label'])->toBe('Kontak');
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'post-sitemap'));
});

it('returns only the homepage with a hint when nothing else is found', function () {
    Http::fake([
        'https://example.ac.id/' => Http::response('<html><body><div id="app"></div></body></html>'),
        '*' => Http::response('', 404),
    ]);

    $result = discover('https://example.ac.id');

    expect(collect($result['pages'])->pluck('path')->all())->toBe(['/'])
        ->and($result['message'])->toContain('add other pages by hand');
});

it('explains when the website cannot be reached', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $result = discover('https://example.ac.id');

    expect($result['pages'])->toBe([])
        ->and($result['message'])->toContain('answered with an error (HTTP 500)');
});

it('is available to staff only, with a clear message for a bad URL', function () use ($menuHtml) {
    Http::fake(['https://example.ac.id/' => Http::response($menuHtml)]);

    $this->post(route('sites.discover'), ['base_url' => 'https://example.ac.id'])->assertRedirect(route('login'));

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->postJson(route('sites.discover'), ['base_url' => 'ftp://example.ac.id'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['base_url' => 'Enter a base URL that starts with http:// or https://.']);

    $this->actingAs($admin)->postJson(route('sites.discover'), ['base_url' => 'https://example.ac.id'])
        ->assertOk()
        ->assertJsonPath('source', 'menu')
        ->assertJsonPath('suggested.0', '/');
});
