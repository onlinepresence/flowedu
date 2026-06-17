@extends('errors.layout')

@section('title', __('Page Not Found'))
@section('code', '404')
@section('status_badge', __('Error 404'))
@section('header_title', __('Page Not Found'))

@section('illustration')
<i class="fa-solid fa-compass text-5xl animate-spin text-purple-600 dark:text-purple-400" style="animation-duration: 15s;"></i>
@endsection

@section('message')
    {{ __('The page you are looking for does not exist, has been removed, or is temporarily unavailable.') }}
@endsection

@section('actions')
    <a href="javascript:history.back()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-arrow-left"></i>
        {{ __('Go Back') }}
    </a>
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-purple-500 active:scale-95 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-house"></i>
        {{ __('Go to Home') }}
    </a>
@endsection
