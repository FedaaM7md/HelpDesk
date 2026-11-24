@extends('layouts.public')

@section('title', __('pages.terms.title').' — '.__('common.app_name'))

@section('content')
    @include('public.pages._document', ['key' => 'terms'])
@endsection
