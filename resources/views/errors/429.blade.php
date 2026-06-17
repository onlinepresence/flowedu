@extends('errors.layout')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('status_badge', __('Error 429'))
@section('header_title', __('Too Many Requests'))

@section('illustration')
<i class="fa-solid fa-gauge-high text-5xl text-amber-500 animate-pulse" style="animation-duration: 2s;"></i>
@endsection

@section('message')
    {{ __('You have sent too many requests in a short time. Please wait a moment and try refreshing the page.') }}
@endsection

@section('actions')
    <button onclick="window.location.reload();" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-purple-500 active:scale-95 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-rotate-right"></i>
        {{ __('Refresh Page') }}
    </button>
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-house"></i>
        {{ __('Go to Home') }}
    </a>
@endsection
