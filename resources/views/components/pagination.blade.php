@props(['paginator'])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
        aria-label="{{ __('common.pagination') ?? 'Pagination' }}">
        <p class="text-xs text-slate-500">
            {{ __('common.showing', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="btn btn-secondary btn-sm pointer-events-none opacity-40">
                        <x-icon name="arrow-left" class="h-4 w-4 flip-icon" />
                        {{ __('common.previous') }}
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">
                        <x-icon name="arrow-left" class="h-4 w-4 flip-icon" />
                        {{ __('common.previous') }}
                    </a>
                @endif

                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand-700 px-2 text-xs font-bold text-white"
                                aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ $page }}</a>
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">
                        {{ __('common.next') }}
                        <x-icon name="arrow-right" class="h-4 w-4 flip-icon" />
                    </a>
                @else
                    <span class="btn btn-secondary btn-sm pointer-events-none opacity-40">
                        {{ __('common.next') }}
                        <x-icon name="arrow-right" class="h-4 w-4 flip-icon" />
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
