{{--
    Pagination (ITK Pagination): Previous / numbered pages / Next, 44px targets, current page marked
    aria-current="page" on primary. Below 520px the numbers collapse to "Page 4 of 12".
    paginator + elements come from Laravel's pagination view. livewire = true uses wire:click
    (previousPage / gotoPage / nextPage) instead of links.
--}}
@props(['paginator', 'elements', 'livewire' => false])

@php
    $base = 'inline-flex items-center justify-center gap-1 min-h-11 min-w-11 px-3 rounded-sm border border-transparent text-callout tabular-nums no-underline';
    $link = $base . ' text-ink hover:bg-surface-sunken cursor-pointer';
    $step = $base . ' text-link hover:bg-surface-sunken cursor-pointer';
    $off = $base . ' text-ink-disabled cursor-default';
    $current = $base . ' bg-primary text-primary-content font-semibold';
@endphp

<nav aria-label="Pagination" {{ $attributes->class('itk-pagination') }}>
    <ul class="flex flex-wrap items-center justify-center gap-1">
        <li>
            @if ($paginator->onFirstPage())
                <span class="{{ $off }}" aria-disabled="true">
                    <x-itk.icon name="chevron-left" size="16" />Previous
                </span>
            @elseif ($livewire)
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="{{ $step }}" rel="prev">
                    <x-itk.icon name="chevron-left" size="16" />Previous
                </button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="{{ $step }}" rel="prev">
                    <x-itk.icon name="chevron-left" size="16" />Previous
                </a>
            @endif
        </li>

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="itk-page-num"><span class="inline-flex min-w-6 justify-center text-ink-muted" aria-hidden="true">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <li class="itk-page-num">
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $current }}" aria-current="page">
                                <span class="sr-only">Page </span>{{ $page }}
                            </span>
                        @elseif ($livewire)
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                class="{{ $link }}" aria-label="Page {{ $page }}">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="{{ $link }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
            @endif
        @endforeach

        <li class="itk-page-compact px-3 text-callout text-ink-muted tabular-nums">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </li>

        <li>
            @if ($paginator->hasMorePages())
                @if ($livewire)
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="{{ $step }}" rel="next">
                        Next<x-itk.icon name="chevron-right" size="16" />
                    </button>
                @else
                    <a href="{{ $paginator->nextPageUrl() }}" class="{{ $step }}" rel="next">
                        Next<x-itk.icon name="chevron-right" size="16" />
                    </a>
                @endif
            @else
                <span class="{{ $off }}" aria-disabled="true">
                    Next<x-itk.icon name="chevron-right" size="16" />
                </span>
            @endif
        </li>
    </ul>
</nav>
