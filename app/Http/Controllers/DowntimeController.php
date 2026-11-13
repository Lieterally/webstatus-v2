<?php

namespace App\Http\Controllers;

use App\Services\OutageTimeline;
use App\Models\DowntimeHistory;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DowntimeController extends Controller
{
    public function index(Request $request): View
    {
        $range = $request->get('range', '7d');
        $siteId = $request->get('site');
        $status = $request->get('status', '');

        $since = match ($range) {
            '1d'  => Carbon::now()->subDay(),
            '7d'  => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            'all' => null,
            default => Carbon::now()->subDays(7),
        };

        // --- Outage Events Query ---
        $query = DowntimeHistory::with('site')
            ->orderBy('started_at', 'desc');

        if ($since) {
            $query->where('started_at', '>=', $since);
        }

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'resolved') {
            $query->where('status', 'resolved');
        } elseif ($status === 'monitoring_stopped') {
            $query->where('status', 'monitoring_stopped');
        }

        $events = $query->paginate(25)->withQueryString();

        // --- Summary Stats ---
        $summaryQuery = DowntimeHistory::query();
        if ($since) {
            $summaryQuery->where('started_at', '>=', $since);
        }

        $totalEvents = (clone $summaryQuery)->count();
        $activeNow = DowntimeHistory::where('status', 'active')->count();

        // Total downtime: sum closed durations + live duration for active ones
        $resolvedSeconds = (clone $summaryQuery)->where('status', '!=', 'active')->sum('duration_seconds');
        $activeSeconds = (clone $summaryQuery)->where('status', 'active')->get()
            ->sum(fn($r) => $r->getLiveDurationSeconds());
        $totalDowntimeSeconds = $resolvedSeconds + $activeSeconds;

        // Most affected site
        $mostAffected = (clone $summaryQuery)
            ->selectRaw('site_id, COUNT(*) as outage_count, SUM(duration_seconds) as total_seconds')
            ->groupBy('site_id')
            ->orderByDesc('outage_count')
            ->first();

        $mostAffectedSite = $mostAffected
            ? Site::find($mostAffected->site_id)?->name
            : null;

        // --- Per-site breakdown ---
        $siteBreakdown = (clone $summaryQuery)
            ->selectRaw('site_id, COUNT(*) as outage_count, SUM(CASE WHEN status != "active" THEN duration_seconds ELSE 0 END) as total_seconds')
            ->groupBy('site_id')
            ->with('site')
            ->orderByDesc('outage_count')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->site?->name ?? 'Unknown',
                    'outage_count' => $row->outage_count,
                    'total_seconds' => $row->total_seconds ?? 0,
                ];
            });

        $sites = Site::orderBy('name')->get(['id', 'name']);

        // --- Gantt chart data (last 24 hours) ---
        $ganttStart = Carbon::now()->subDay();
        $ganttEvents = DowntimeHistory::with('site')
            ->where(function ($q) use ($ganttStart) {
                $q->where('started_at', '>=', $ganttStart)
                    ->orWhere(function ($q2) use ($ganttStart) {
                        $q2->where('started_at', '<', $ganttStart)
                            ->where(function ($q3) use ($ganttStart) {
                                $q3->whereNull('ended_at')
                                    ->orWhere('ended_at', '>=', $ganttStart);
                            });
                    });
            })
            ->orderBy('started_at')
            ->get();

        $now = Carbon::now();
        $spans = [];
        $ganttData = [];
        $downPerSite = [];
        foreach ($ganttEvents as $event) {
            $siteName = $event->site?->name ?? 'Unknown';
            $barStart = $event->started_at->lt($ganttStart) ? $ganttStart : $event->started_at;
            $barEnd = $event->ended_at ?? $now;

            $spans[] = ['site' => $siteName, 'start' => $barStart, 'end' => $event->ended_at, 'active' => $event->isActive()];
            $downPerSite[$siteName] = ($downPerSite[$siteName] ?? 0) + max(0, (int) $barStart->diffInSeconds($barEnd));
            $ganttData[] = [
                'x' => $siteName,
                'y' => [$barStart->getTimestampMs(), $barEnd->getTimestampMs()],
                'state' => $event->isActive() ? 'active' : 'resolved',
            ];
        }

        // "By website" view: most downtime first, so the top rows matter most
        arsort($downPerSite);
        $ganttLabels = array_keys($downPerSite);
        $order = array_flip($ganttLabels);
        usort($ganttData, fn ($a, $b) => $order[$a['x']] <=> $order[$b['x']]);

        // "Incidents" view: overlapping outages grouped, and how many websites were down over time
        $timeline = app(OutageTimeline::class);
        $incidents = $timeline->incidents($spans, $now);
        $concurrency = $timeline->concurrency($spans, $ganttStart, $now);
        $peakDown = $concurrency ? max(array_column($concurrency, 1)) : 0;

        return view('downtime.index', compact(
            'events',
            'range',
            'siteId',
            'status',
            'sites',
            'totalEvents',
            'activeNow',
            'totalDowntimeSeconds',
            'mostAffectedSite',
            'siteBreakdown',
            'ganttData',
            'ganttLabels',
            'ganttStart',
            'incidents',
            'concurrency',
            'peakDown',
        ));
    }
}
