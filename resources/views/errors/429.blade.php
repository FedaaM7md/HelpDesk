@extends('layouts.public')

@section('title', __('errors.429.title'))

@section('content')
    <div class="mx-auto max-w-xl px-4 py-20 text-center sm:px-6">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
            <x-icon name="alert" class="h-8 w-8" />
        </span>
        <p class="mt-6 text-5xl font-bold text-slate-200">429</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('errors.429.title') }}</h1>
        <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-slate-500">{{ __('errors.429.message') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-primary">{{ __('errors.back_home') }}</a>
        </div>
    </div>
@endsection
