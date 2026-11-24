@extends('layouts.public')

@section('title', __('pages.about.title').' — '.__('common.app_name'))

@section('content')
    @include('public.pages._document', ['key' => 'about'])
@endsection
