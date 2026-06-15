@if ($paginator->hasPages())
    <div class="flex justify-center">
        <div class="join">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <button class="join-item btn btn-sm btn-disabled">«</button>
            @else
                <button wire:click="previousPage" class="join-item btn btn-sm">«</button>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <button class="join-item btn btn-sm btn-disabled">{{ $element }}</button>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button class="join-item btn btn-sm btn-active">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})"
                                class="join-item btn btn-sm">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" class="join-item btn btn-sm">»</button>
            @else
                <button class="join-item btn btn-sm btn-disabled">»</button>
            @endif
        </div>
    </div>
@endif
