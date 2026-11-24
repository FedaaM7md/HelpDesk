@props(['article'])

<a href="{{ route('kb.show', $article) }}" class="group card flex h-full flex-col p-6 transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md">
    <div class="flex items-center justify-between gap-3">
        <span class="badge bg-brand-50 text-brand-700 ring-brand-100">{{ $article->category?->name() }}</span>
        <span class="text-[10px] font-medium uppercase tracking-wide text-slate-400">{{ __('kb.reading_time', ['count' => $article->readingMinutes()]) }}</span>
    </div>
    <h3 class="mt-4 text-base font-bold leading-7 text-slate-900 group-hover:text-brand-700">{{ $article->title }}</h3>
    <p class="mt-2 flex-1 text-sm leading-7 text-slate-500">{{ $article->excerptText() }}</p>
    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-[11px] text-slate-400">
        <span class="inline-flex items-center gap-1.5">
            <x-icon name="eye" class="h-3.5 w-3.5" />
            {{ __('kb.views', ['count' => $article->views]) }}
        </span>
        <span class="inline-flex items-center gap-1 font-semibold text-brand-700 group-hover:text-teal-700">
            {{ __('kb.read_more') }}
            <x-icon name="arrow-right" class="h-3.5 w-3.5 flip-icon" />
        </span>
    </div>
</a>
