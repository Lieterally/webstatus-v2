{{--
    Site status as a badge with icon and word (never colour alone).
    status: App\Enums\SiteStatus (or its string value). monitored = false shows "Not monitored".
--}}
@props(['status' => null, 'monitored' => true])

@php
    $value = $status instanceof \App\Enums\SiteStatus ? $status->value : $status;
    [$tone, $icon, $word] = match (true) {
        ! $monitored => ['neutral', 'circle-pause', 'Not monitored'],
        $value === 'up' => ['success', 'circle-check', 'Up'],
        $value === 'partially_down' => ['warning', 'triangle-alert', 'Partially down'],
        $value === 'totally_down' => ['danger', 'circle-x', 'Down'],
        default => ['neutral', 'circle-help', 'Unknown'],
    };
@endphp

<x-itk.badge :tone="$tone" :icon="$icon" {{ $attributes }}>{{ $word }}</x-itk.badge>
