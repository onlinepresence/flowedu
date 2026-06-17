@extends('errors.layout')

@section('title', __('Page Expired'))
@section('code', '419')
@section('status_badge', __('Error 419'))
@section('header_title', __('Session or Page Expired'))

@section('illustration')
<i class="fa-solid fa-hourglass-half text-5xl animate-pulse text-purple-600 dark:text-purple-400" style="animation-duration: 2.5s;"></i>
@endsection

@section('message')
    {{ __('Your page token expired due to inactivity. Simply refresh the page and submit your request again.') }}
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
