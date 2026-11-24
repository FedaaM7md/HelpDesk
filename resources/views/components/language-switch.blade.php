@php($current = app()->getLocale())

<div class="inline-flex items-center rounded-full border border-slate-200 bg-white p-1 text-xs font-semibold shadow-sm"
    role="group" aria-label="{{ __('common.switch_language') }}">
    <a href="{{ route('language.switch', ['locale' => 'ar']) }}" hreflang="ar" lang="ar"
        class="flex min-h-11 min-w-11 items-center justify-center rounded-full px-2.5 transition-colors lg:min-h-0 lg:min-w-0 lg:py-1 {{ $current === 'ar' ? 'bg-brand-700 text-white' : 'text-slate-500 hover:text-slate-900' }}"
        @if ($current === 'ar') aria-current="true" @endif>
        ع
    </a>
    <a href="{{ route('language.switch', ['locale' => 'en']) }}" hreflang="en" lang="en"
        class="flex min-h-11 min-w-11 items-center justify-center rounded-full px-2.5 transition-colors lg:min-h-0 lg:min-w-0 lg:py-1 {{ $current === 'en' ? 'bg-brand-700 text-white' : 'text-slate-500 hover:text-slate-900' }}"
        @if ($current === 'en') aria-current="true" @endif>
        EN
    </a>
</div>
