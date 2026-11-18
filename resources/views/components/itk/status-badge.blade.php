{{--
    Site status as a badge with icon and word (never colour alone).
    status: App\Enums\SiteStatus (or its string value). monitored = false shows "Not monitored".
--}}
@props(['status' => null, 'monitored' => true])

@php
    $value = $status instanceof \App\Enums\SiteStatus ? $status->value : $status;
    [$tone, $icon, $word, $key] = match (true) {
        ! $monitored => ['neutral', 'circle-pause', 'Not monitored', 'not_monitored'],
        $value === 'up' => ['success', 'circle-check', 'Up', 'up'],
        $value === 'partially_down' => ['warning', 'triangle-alert', 'Partially down', 'partially_down'],
        $value === 'totally_down' => ['danger', 'circle-x', 'Down', 'totally_down'],
        default => ['neutral', 'circle-help', 'Unknown', 'unknown'],
    };
@endphp

<x-itk.badge :tone="$tone" :icon="$icon" data-status="{{ $key }}" {{ $attributes }}>{{ $word }}</x-itk.badge>
