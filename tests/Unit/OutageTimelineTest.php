<?php

use App\Services\OutageTimeline;
use Carbon\CarbonImmutable;

function span(string $site, string $start, ?string $end, bool $active = false): array
{
    return [
        'site' => $site,
        'start' => CarbonImmutable::parse($start),
        'end' => $end ? CarbonImmutable::parse($end) : null,
        'active' => $active,
    ];
}

beforeEach(function () {
    $this->timeline = new OutageTimeline();
    $this->now = CarbonImmutable::parse('2026-11-13 17:00:00');
});

it('groups overlapping outages into one incident and keeps separate ones apart', function () {
    $incidents = $this->timeline->incidents([
        span('A', '2026-11-13 12:04', '2026-11-13 12:11'),
        span('B', '2026-11-13 12:05', '2026-11-13 12:10'),
        span('C', '2026-11-13 12:12', '2026-11-13 12:15'), // starts 1 min after the group ends: joins
        span('A', '2026-11-13 15:10', '2026-11-13 15:14'), // on its own
    ], $this->now);

    expect($incidents)->toHaveCount(2)
        ->and($incidents[0]['sites'])->toBe(['A'])
        ->and($incidents[0]['duration_seconds'])->toBe(240)
        ->and($incidents[1]['sites'])->toBe(['A', 'B', 'C'])
        ->and($incidents[1]['count'])->toBe(3)
        ->and($incidents[1]['start']->format('H:i'))->toBe('12:04')
        ->and($incidents[1]['end']->format('H:i'))->toBe('12:15');
});

it('merges an outage that starts while another is still ongoing', function () {
    $incidents = $this->timeline->incidents([
        span('B', '2026-11-13 10:00', null, true),
        span('A', '2026-11-13 16:00', '2026-11-13 16:05'),
    ], $this->now);

    expect($incidents)->toHaveCount(1)
        ->and($incidents[0]['sites'])->toBe(['A', 'B'])
        ->and($incidents[0]['ongoing'])->toBeTrue();
});

it('does not merge outages more than two minutes apart', function () {
    $incidents = $this->timeline->incidents([
        span('A', '2026-11-13 12:00', '2026-11-13 12:05'),
        span('B', '2026-11-13 12:08', '2026-11-13 12:09'),
    ], $this->now);

    expect($incidents)->toHaveCount(2);
});

it('lists ongoing incidents first, with no end and a duration up to now', function () {
    $incidents = $this->timeline->incidents([
        span('A', '2026-11-13 08:00', '2026-11-13 08:05'), // before B started: a separate incident
        span('B', '2026-11-13 10:00', null, true),
    ], $this->now);

    expect($incidents[0]['ongoing'])->toBeTrue()
        ->and($incidents[0]['end'])->toBeNull()
        ->and($incidents[0]['duration_seconds'])->toBe(7 * 3600)
        ->and($incidents[1]['sites'])->toBe(['A']);
});

it('counts websites down over time, once per website', function () {
    $start = CarbonImmutable::parse('2026-11-12 17:00');
    $points = $this->timeline->concurrency([
        span('A', '2026-11-13 12:00', '2026-11-13 12:10'),
        span('A', '2026-11-13 12:05', '2026-11-13 12:08'), // overlapping record for the same site
        span('B', '2026-11-13 12:02', '2026-11-13 12:10'),
    ], $start, $this->now);

    $at = fn (string $t) => collect($points)->last(fn ($p) => $p[0] <= CarbonImmutable::parse($t)->getTimestampMs())[1];

    expect($points[0])->toBe([$start->getTimestampMs(), 0])
        ->and($at('2026-11-13 12:01'))->toBe(1)
        ->and($at('2026-11-13 12:06'))->toBe(2)
        ->and($at('2026-11-13 12:09'))->toBe(2)
        ->and($at('2026-11-13 12:11'))->toBe(0)
        ->and(end($points))->toBe([$this->now->getTimestampMs(), 0]);
});

it('clips outages that started before the window', function () {
    $start = CarbonImmutable::parse('2026-11-12 17:00');
    $points = $this->timeline->concurrency([span('A', '2026-11-12 10:00', null, true)], $start, $this->now);

    expect($points[0])->toBe([$start->getTimestampMs(), 1])
        ->and(end($points)[1])->toBe(1);
});
