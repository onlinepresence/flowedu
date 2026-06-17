@extends('errors.layout')

@section('title', __('Authentication Required'))
@section('code', '401')
@section('status_badge', __('Error 401'))
@section('header_title', __('Session Expired or Login Required'))

@section('illustration')
<i class="fa-solid fa-user-lock text-5xl animate-bounce text-purple-600 dark:text-purple-400" style="animation-duration: 2s;"></i>
@endsection

@section('message')
    {{ __('Your login session has expired, or you need to authenticate to view this page.') }}
@endsection

@section('actions')
    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-purple-500 active:scale-95 transition duration-200 w-full sm:w-auto">
        <i class="fa-solid fa-right-to-bracket"></i>
        {{ __('Log In Again') }}
    </a>
@endsection
