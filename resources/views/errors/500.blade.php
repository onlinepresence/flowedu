@extends('errors.layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('status_badge', __('Error 500'))
@section('header_title', __('Something Went Wrong'))

@section('illustration')
<i class="fa-solid fa-triangle-exclamation text-5xl text-rose-500 animate-pulse" style="animation-duration: 2s;"></i>
@endsection

@section('message')
    {{ __('An internal system error occurred while processing your request. The administration has been notified and is looking into this.') }}
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
