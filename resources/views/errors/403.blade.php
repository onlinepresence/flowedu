@extends('errors.layout')

@section('title', __('Access Denied'))
@section('code', '403')
@section('status_badge', __('Error 403'))
@section('header_title', __('Access Denied'))

@section('illustration')
<i class="fa-solid fa-shield-halved text-5xl text-rose-500 dark:text-rose-400"></i>
@endsection

@section('message')
    {{ __('You do not have permission to access this resource. Please contact system support if you believe this is a mistake.') }}
@endsection

@section('actions')
    <a href="javascript:history.back()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-arrow-left"></i>
        {{ __('Go Back') }}
    </a>
    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-purple-500 active:scale-95 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-gauge-high"></i>
        {{ __('Go to Dashboard') }}
    </a>
@endsection
