{{--
    Trace (ITK Trace): a vertical timeline of steps.
    steps: [['title' => ..., 'status' => 'done'|'current'|'pending'|'error', 'time' => ?, 'detail' => ?, 'word' => ?], ...]
    word overrides the hidden status word (default Done / In progress / Not yet / Problem).
    State is carried by the node shape (check, ring, cross), a hidden status word and the line, not colour alone.
--}}
@props(['steps' => [], 'label' => null])

@php
    $words = ['done' => 'Done', 'current' => 'In progress', 'pending' => 'Not yet', 'error' => 'Problem'];
@endphp

<ol @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class('grid') }}>
    @foreach ($steps as $step)
        @php $status = $step['status'] ?? 'pending'; @endphp
        <li @class([
            'relative grid grid-cols-[24px_1fr] gap-3',
            'pb-5' => ! $loop->last,
        ])>
            @unless ($loop->last)
                <span aria-hidden="true" @class([
                    'absolute left-2.75 top-6.5 bottom-0.5 w-0.5',
                    'bg-primary' => $status === 'done',
                    'bg-line' => $status !== 'done',
                ])></span>
            @endunless
            <span aria-hidden="true" @class([
                'grid place-items-center size-6 rounded-full',
                'bg-primary text-primary-content' => $status === 'done',
                'border-7 border-primary bg-surface' => $status === 'current',
                'border-2 border-line-strong bg-surface' => $status === 'pending',
                'bg-error-fill text-error-content' => $status === 'error',
            ])>
                @if ($status === 'done')
                    <x-itk.icon name="check" size="14" />
                @elseif ($status === 'error')
                    <x-itk.icon name="x" size="14" />
                @endif
            </span>
            <div class="grid gap-1 min-w-0 pt-px">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span @class([
                        'text-body font-semibold',
                        'text-ink' => in_array($status, ['done', 'current']),
                        'text-ink-muted font-medium' => $status === 'pending',
                        'text-error' => $status === 'error',
                    ])><span class="sr-only">{{ $step['word'] ?? ($words[$status] ?? '') }}: </span>{{ $step['title'] }}</span>
                    @if (! empty($step['time']))
                        <span class="text-footnote text-ink-muted tabular-nums">{{ $step['time'] }}</span>
                    @endif
                </div>
                @if (! empty($step['detail']))
                    <p class="text-callout font-normal text-ink-muted">{{ $step['detail'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
