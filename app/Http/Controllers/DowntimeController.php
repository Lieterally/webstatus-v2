<?php

namespace App\Http\Controllers;

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
        }

        $events = $query->paginate(25)->withQueryString();

        // --- Summary Stats ---
        $summaryQuery = DowntimeHistory::query();
        if ($since) {
            $summaryQuery->where('started_at', '>=', $since);
        }

        $totalEvents = (clone $summaryQuery)->count();
        $activeNow = DowntimeHistory::where('status', 'active')->count();

        // Total downtime: sum resolved durations + live duration for active ones
        $resolvedSeconds = (clone $summaryQuery)->where('status', 'resolved')->sum('duration_seconds');
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
            ->selectRaw('site_id, COUNT(*) as outage_count, SUM(CASE WHEN status = "resolved" THEN duration_seconds ELSE 0 END) as total_seconds')
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
        ));
    }
}
