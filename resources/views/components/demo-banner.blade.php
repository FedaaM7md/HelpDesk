@if (config('helpdesk.demo'))
    <div class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto flex max-w-7xl items-center justify-center gap-1.5 px-4 py-1 text-center text-[10px] font-medium text-slate-500">
            <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
            <span>{{ __('common.demo_banner') }}</span>
        </div>
    </div>
@endif
