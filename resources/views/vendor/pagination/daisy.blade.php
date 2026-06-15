@if ($paginator->hasPages())
    <div class="flex justify-center">
        <div class="join">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <button class="join-item btn btn btn-disabled">«</button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="join-item btn btn">«</a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <button class="join-item btn btn btn-disabled">{{ $element }}</button>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button class="join-item btn btn btn-active">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="join-item btn btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="join-item btn btn">»</a>
            @else
                <button class="join-item btn btn btn-disabled">»</button>
            @endif
        </div>
    </div>
@endif
