@if ($paginator->hasPages())
    <nav class="d-flex justify-content-center mt-4">
        <div class="d-flex align-items-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="btn btn-outline-secondary btn-sm disabled me-2">
                    <i class="fas fa-chevron-left"></i> Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-outline-primary btn-sm me-2" rel="prev">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            @endif

            {{-- Pagination Elements --}}
            <div class="d-flex align-items-center">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="btn btn-outline-secondary btn-sm disabled mx-1">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="btn btn-primary btn-sm mx-1">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-outline-primary btn-sm mx-1">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-outline-primary btn-sm ms-2" rel="next">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span class="btn btn-outline-secondary btn-sm disabled ms-2">
                    Next <i class="fas fa-chevron-right"></i>
                </span>
            @endif
        </div>
    </nav>

    {{-- Results Info --}}
    <div class="d-flex justify-content-center mt-2">
        <p class="small text-muted mb-0">
            Menampilkan {{ $paginator->firstItem() }} sampai {{ $paginator->lastItem() }} dari {{ $paginator->total() }} hasil
        </p>
    </div>
@endif