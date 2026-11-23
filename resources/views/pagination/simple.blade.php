@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between">
        @if ($paginator->onFirstPage())
            <span class="text-xs font-semibold text-slate-300">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="text-xs font-semibold text-uew-royal hover:underline">Previous</a>
        @endif
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="text-xs font-semibold text-uew-royal hover:underline">Next</a>
        @else
            <span class="text-xs font-semibold text-slate-300">Next</span>
        @endif
    </nav>
@endif
