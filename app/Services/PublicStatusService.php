<?php

namespace App\Services;

use App\Models\DailyStat;
use App\Models\DowntimeHistory;
use App\Models\Site;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the data for the public status page (docs/PUBLIC-STATUS-PLAN.md).
 *
 * Only sites that are public AND monitored appear. The snapshot holds nothing private:
 * no responsible person, page paths, HTTP codes, error types or response times.
 * It is cached for a minute and dropped at the end of every monitoring cycle.
 */
class PublicStatusService
{
    public const CACHE_KEY = 'public-status';

    public const CACHE_SECONDS = 60;

    public const HISTORY_DAYS = 90;

    public const INCIDENT_DAYS = 14;

    /** A last check older than this many cycle intervals marks the page as out of date. */
    public const STALE_AFTER_INTERVALS = 3;

    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => $this->build());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function build(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $intervalMinutes = max(1, (int) SystemConfig::getValue('cycle_interval_minutes', '10'));
        $monitoringEnabled = SystemConfig::isMonitoringEnabled();
        $lastCheckedRaw = SystemConfig::getValue('last_cycle_completed_at');
        $lastChecked = $lastCheckedRaw ? CarbonImmutable::parse($lastCheckedRaw) : null;
        $stale = $lastChecked === null
            || $lastChecked->lt($now->subMinutes($intervalMinutes * self::STALE_AFTER_INTERVALS));
        $statusKnown = $monitoringEnabled && ! $stale;

        $sites = Site::query()
            ->with('category:id,name')
            ->where('is_public', true)
            ->where('is_monitored', true)
            ->orderBy('name')
            ->get(['id', 'name', 'public_name', 'base_url', 'category_id', 'status']);

        $firstDay = $now->startOfDay()->subDays(self::HISTORY_DAYS - 1);
        $stats = DailyStat::query()
            ->whereIn('site_id', $sites->pluck('id'))
            ->where('period_start', '>=', $firstDay->toDateString())
            ->get(['site_id', 'period_start', 'downtime_seconds', 'checks_count'])
            ->groupBy('site_id');

        $outagesPerDay = DowntimeHistory::query()
            ->whereIn('site_id', $sites->pluck('id'))
            ->where('started_at', '>=', $firstDay)
            ->get(['site_id', 'started_at'])
            ->groupBy(fn ($h) => $h->site_id . '|' . $h->started_at->toDateString())
            ->map->count();

        $siteRows = $sites->map(fn (Site $site) => $this->siteRow(
            $site, $stats->get($site->id, collect()), $outagesPerDay, $firstDay, $now, $statusKnown,
        ));

        $categories = $siteRows
            ->groupBy('category')
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'sites' => $rows->values()->all(),
                'has_problem' => $rows->contains(fn ($r) => in_array($r['status'], ['partially_down', 'totally_down'], true)),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        $state = $this->overallState($siteRows, $monitoringEnabled, $stale);
        $incidents = $this->incidents($sites, $now);

        return [
            // Dates are ISO 8601 strings: the cache only unserialises plain values
            'generated_at' => $now->toIso8601String(),
            'version' => $this->version($lastChecked, $monitoringEnabled, $state, $siteRows, $incidents),
            'last_checked_at' => $lastChecked?->toIso8601String(),
            'interval_minutes' => $intervalMinutes,
            'monitoring_enabled' => $monitoringEnabled,
            'state' => $state,
            'counts' => [
                'sites' => $siteRows->count(),
                'down' => $siteRows->where('status', 'totally_down')->count(),
                'degraded' => $siteRows->where('status', 'partially_down')->count(),
            ],
            'history_days' => self::HISTORY_DAYS,
            'categories' => $categories,
            'incidents' => $incidents,
        ];
    }

    private function siteRow(Site $site, Collection $stats, Collection $outagesPerDay, CarbonImmutable $firstDay, CarbonImmutable $now, bool $statusKnown): array
    {
        $byDate = $stats->keyBy(fn ($s) => Carbon::parse($s->period_start)->toDateString());
        $days = [];
        $totalDown = 0;
        $totalObserved = 0;

        for ($i = 0; $i < self::HISTORY_DAYS; $i++) {
            $day = $firstDay->addDays($i);
            $date = $day->toDateString();
            $stat = $byDate->get($date);
            $outages = (int) $outagesPerDay->get($site->id . '|' . $date, 0);

            if (! $stat || (int) $stat->checks_count === 0) {
                $days[] = ['date' => $date, 'uptime' => null, 'downtime_seconds' => 0, 'outages' => $outages, 'tone' => 'none'];
                continue;
            }

            // Past days are 24 hours; today only counts the time elapsed so far.
            $observed = $day->isSameDay($now) ? max(1, (int) $day->diffInSeconds($now)) : 86400;
            $down = min((int) $stat->downtime_seconds, $observed);
            $uptime = round(100 * (1 - $down / $observed), 2);
            $totalDown += $down;
            $totalObserved += $observed;

            $days[] = [
                'date' => $date,
                'uptime' => $uptime,
                'downtime_seconds' => $down,
                'outages' => $outages,
                'tone' => $this->dayTone($uptime),
            ];
        }

        return [
            'id' => $site->id,
            'name' => $site->publicName(),
            'url' => $site->base_url,
            'host' => parse_url($site->base_url, PHP_URL_HOST) ?: $site->base_url,
            'category' => $site->category?->name ?? 'Other',
            'status' => $statusKnown ? $site->status->value : 'unknown',
            'uptime' => $totalObserved > 0 ? round(100 * (1 - $totalDown / $totalObserved), 2) : null,
            'days' => $days,
        ];
    }

    /**
     * Changes only when something a visitor would notice changes: a new check, monitoring on/off,
     * the overall state, a site's name or status, or an incident starting or ending. It leaves out
     * values that drift with the clock (build time, today's uptime %, ongoing durations), so the
     * page's "status has been updated" notice doesn't fire on every cache rebuild.
     */
    private function version(?CarbonImmutable $lastChecked, bool $monitoringEnabled, string $state, Collection $siteRows, array $incidents): string
    {
        return md5(json_encode([
            $lastChecked?->toIso8601String(),
            $monitoringEnabled,
            $state,
            $siteRows->map(fn ($r) => [$r['id'], $r['name'], $r['category'], $r['status']])->values()->all(),
            array_map(fn ($i) => [$i['site'], $i['state'], $i['started_at'], $i['ended_at']], $incidents),
        ]));
    }

    /** "45 s", "11 min", "2 h 5 min", "3 d 4 h" */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' s';
        }
        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . ' h' . ($minutes % 60 ? ' ' . ($minutes % 60) . ' min' : '');
        }

        return intdiv($hours, 24) . ' d' . ($hours % 24 ? ' ' . ($hours % 24) . ' h' : '');
    }

    /** Brand book format: "15.50 WITA" today, "12 Nov 2026, 15.50 WITA" otherwise. */
    public static function formatTime(\DateTimeInterface|string|null $time, \DateTimeInterface|string|null $now = null): string
    {
        if (! $time) {
            return '';
        }
        $time = CarbonImmutable::parse($time)->setTimezone(config('app.timezone'));
        $now = $now ? CarbonImmutable::parse($now)->setTimezone(config('app.timezone')) : CarbonImmutable::now();
        $clock = $time->format('H.i') . ' WITA';

        return $time->isSameDay($now) ? $clock : $time->format('j M Y') . ', ' . $clock;
    }

    /** none = no checks that day, ok = 100%, minor = 99% or more, major = below 99%. */
    public function dayTone(?float $uptime): string
    {
        return match (true) {
            $uptime === null => 'none',
            $uptime >= 100.0 => 'ok',
            $uptime >= 99.0 => 'minor',
            default => 'major',
        };
    }

    private function overallState(Collection $rows, bool $monitoringEnabled, bool $stale): string
    {
        return match (true) {
            $rows->isEmpty() => 'empty',
            ! $monitoringEnabled => 'paused',
            $stale => 'stale',
            $rows->contains('status', 'totally_down') => 'down',
            $rows->contains('status', 'partially_down') => 'degraded',
            default => 'operational',
        };
    }

    private function incidents(Collection $sites, CarbonImmutable $now): array
    {
        $names = $sites->mapWithKeys(fn (Site $s) => [$s->id => $s->publicName()]);

        return DowntimeHistory::query()
            ->whereIn('site_id', $sites->pluck('id'))
            ->where(fn ($q) => $q->where('status', 'active')
                ->orWhere('started_at', '>=', $now->subDays(self::INCIDENT_DAYS)))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('started_at')
            ->limit(50)
            ->get()
            ->map(fn (DowntimeHistory $h) => [
                'site' => $names[$h->site_id] ?? '',
                'state' => $h->isActive() ? 'ongoing' : ($h->wasMonitoringStopped() ? 'monitoring_stopped' : 'resolved'),
                'started_at' => CarbonImmutable::parse($h->started_at)->toIso8601String(),
                'ended_at' => $h->ended_at ? CarbonImmutable::parse($h->ended_at)->toIso8601String() : null,
                'duration_seconds' => $h->getLiveDurationSeconds(),
                'pages_count' => count($h->affected_pages ?? []),
            ])
            ->all();
    }
}
