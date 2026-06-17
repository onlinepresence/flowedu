@extends('errors.layout')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('status_badge', __('Error 503'))
@section('header_title', __('Under Maintenance'))

@section('illustration')
<i class="fa-solid fa-screwdriver-wrench text-5xl text-purple-600 dark:text-purple-400 animate-pulse" style="animation-duration: 2.5s;"></i>
@endsection

@section('message')
    {{ __('We are currently performing routine maintenance or database updates. Please check back in a few minutes.') }}
@endsection

@section('actions')
    <button onclick="window.location.reload();" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-purple-500 active:scale-95 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-rotate-right"></i>
        {{ __('Refresh Page') }}
    </button>
@endsection
