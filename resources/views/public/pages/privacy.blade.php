@extends('layouts.public')

@section('title', __('pages.privacy.title').' — '.__('common.app_name'))

@section('content')
    @include('public.pages._document', ['key' => 'privacy'])
@endsection
