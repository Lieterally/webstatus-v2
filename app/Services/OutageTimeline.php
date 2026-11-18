<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Turns per-website outage spans into the two views on the downtime page:
 * incidents (overlapping outages grouped together) and a "websites down over time" series.
 *
 * A span is ['site' => string, 'start' => CarbonInterface, 'end' => ?CarbonInterface, 'active' => bool].
 * An active span (no end) runs until $now.
 */
class OutageTimeline
{
    /** Outages that start within this many seconds of an incident's end join that incident. */
    public const MERGE_GAP_SECONDS = 120;

    /**
     * Group overlapping outages. Returns incidents, ongoing first, then newest first:
     * ['start', 'end' (null while ongoing), 'duration_seconds', 'sites' (sorted names), 'count', 'ongoing'].
     */
    public function incidents(array $spans, CarbonInterface $now): array
    {
        usort($spans, fn ($a, $b) => $a['start'] <=> $b['start']);

        $groups = [];
        foreach ($spans as $span) {
            $end = $span['end'] ?? $now;
            $last = array_key_last($groups);

            if ($last !== null && $span['start']->lte($groups[$last]['end']->copy()->addSeconds(self::MERGE_GAP_SECONDS))) {
                $group = &$groups[$last];
                $group['end'] = $end->gt($group['end']) ? $end : $group['end'];
                $group['sites'][$span['site']] = true;
                $group['ongoing'] = $group['ongoing'] || $span['active'];
                unset($group);
                continue;
            }

            $groups[] = [
                'start' => $span['start'],
                'end' => $end,
                'sites' => [$span['site'] => true],
                'ongoing' => $span['active'],
            ];
        }

        $incidents = array_map(function ($g) use ($now) {
            $sites = array_keys($g['sites']);
            sort($sites, SORT_NATURAL | SORT_FLAG_CASE);
            $end = $g['ongoing'] ? $now : $g['end'];

            return [
                'start' => CarbonImmutable::instance($g['start']),
                'end' => $g['ongoing'] ? null : CarbonImmutable::instance($g['end']),
                'duration_seconds' => max(0, (int) $g['start']->diffInSeconds($end)),
                'sites' => $sites,
                'count' => count($sites),
                'ongoing' => $g['ongoing'],
            ];
        }, $groups);

        usort($incidents, fn ($a, $b) => [$b['ongoing'], $b['start']] <=> [$a['ongoing'], $a['start']]);

        return $incidents;
    }

    /**
     * Number of websites down over the window, as step points [timestamp ms, count], starting at
     * $windowStart and ending at $now. A website with overlapping outages counts once.
     */
    public function concurrency(array $spans, CarbonInterface $windowStart, CarbonInterface $now): array
    {
        $changes = [];
        foreach ($spans as $span) {
            $start = $span['start']->lt($windowStart) ? $windowStart : $span['start'];
            $end = $span['end'] ?? $now;
            if ($end->lte($start)) {
                continue;
            }
            $changes[] = [$start->getTimestampMs(), +1, $span['site']];
            if ($span['end'] !== null) {
                // An ongoing outage has no end: it is still counted at $now
                $changes[] = [$end->getTimestampMs(), -1, $span['site']];
            }
        }

        // Ends before starts at the same instant, so back-to-back outages don't spike the count
        usort($changes, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $open = [];
        $points = [[$windowStart->getTimestampMs(), 0]];
        foreach ($changes as [$time, $delta, $site]) {
            $open[$site] = ($open[$site] ?? 0) + $delta;
            if ($open[$site] <= 0) {
                unset($open[$site]);
            }
            $count = count($open);
            if (end($points)[0] === $time) {
                $points[array_key_last($points)][1] = $count;
            } else {
                $points[] = [$time, $count];
            }
        }
        if (end($points)[0] < $now->getTimestampMs()) {
            $points[] = [$now->getTimestampMs(), count($open)];
        }

        return $points;
    }
}
