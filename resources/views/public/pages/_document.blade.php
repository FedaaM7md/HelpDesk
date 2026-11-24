@php
    $page = __('pages.'.$key);
    $locale = app()->getLocale();
    $company = config('helpdesk.company.'.($locale === 'ar' ? 'name_ar' : 'name_en'));
    $address = config('helpdesk.company.'.($locale === 'ar' ? 'address_ar' : 'address_en'));
@endphp

<section class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <p class="eyebrow">{{ $page['eyebrow'] }}</p>
    <h1 class="mt-2 text-3xl font-bold text-ink">{{ $page['title'] }}</h1>
    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $page['intro'] }}</p>

    <div class="mt-8 space-y-4">
        @foreach ($page['sections'] as $section)
            <div class="card p-5">
                <h2 class="text-base font-bold text-ink">{{ $section['heading'] }}</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">{{ $section['body'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="card mt-8 p-5">
        <h2 class="text-base font-bold text-ink">{{ $page['contact_heading'] }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $page['contact_note'] }}</p>
        <ul class="mt-4 space-y-2.5 text-sm text-slate-700">
            <li class="flex items-center gap-2">
                <x-icon name="headset" class="h-4 w-4 text-brand-600" />
                <span dir="ltr">{{ config('helpdesk.company.phone') }}</span>
            </li>
            <li class="flex items-center gap-2">
                <x-icon name="mail" class="h-4 w-4 text-brand-600" />
                <span dir="ltr">{{ config('helpdesk.company.email') }}</span>
            </li>
            <li class="flex items-center gap-2">
                <x-icon name="home" class="h-4 w-4 text-brand-600" />
                <span>{{ $company }} — {{ $address }}</span>
            </li>
        </ul>
        <p class="mt-4 text-[11px] text-slate-400">{{ __('pages.last_updated') }}</p>
    </div>

    <div class="mt-8 flex flex-wrap gap-2">
        <a href="{{ route('portal.submit') }}" class="btn-primary btn-sm">{{ __('common.nav.submit_ticket') }}</a>
        <a href="{{ route('kb.index') }}" class="btn-secondary btn-sm">{{ __('common.nav.knowledge_base') }}</a>
    </div>
</section>
