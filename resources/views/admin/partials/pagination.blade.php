{{-- Pagination de l'admin (le site public n'a pas de listes paginees) --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        <span>Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }} · {{ $paginator->total() }} élément(s)</span>
        <span class="pagination-links">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true">‹ Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Précédent</a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true">{{ $element }}</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant ›</a>
            @else
                <span aria-disabled="true">Suivant ›</span>
            @endif
        </span>
    </nav>
@endif
