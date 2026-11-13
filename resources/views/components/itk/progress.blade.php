{{-- Progress bar (ITK Progress). label names what progresses; valueText is what screen readers hear. --}}
@props(['value' => 0, 'max' => 100, 'label', 'valueText' => null, 'showValue' => true])

@php $pct = $max > 0 ? max(0, min(100, round($value / $max * 100))) : 0; @endphp

<div {{ $attributes->class('grid gap-1.5') }}>
    <div class="flex justify-between text-callout text-ink">
        <span>{{ $label }}</span>
        @if ($showValue)
            <span class="tabular-nums text-ink-muted">{{ $valueText ?? $pct . '%' }}</span>
        @endif
    </div>
    <div role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="{{ $max }}"
        aria-valuenow="{{ $value }}" @if ($valueText) aria-valuetext="{{ $valueText }}" @endif
        class="h-2 rounded-full bg-surface-sunken ring-1 ring-inset ring-line overflow-hidden">
        <div class="h-full rounded-full bg-primary transition-[width] duration-200" style="width: {{ $pct }}%"></div>
    </div>
</div>
