@if (session('status'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900"
        role="status">
        <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0 text-teal-600" />
        <p class="leading-6">{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"
        role="alert">
        <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
        <div>
            <p class="font-semibold">{{ __('common.form_errors') ?? 'Please review the highlighted fields.' }}</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
