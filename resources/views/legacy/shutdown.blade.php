<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Maintenance') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Application unavailable') }}</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('The system is temporarily shut down.') }}</p>
    </div>
</body>
</html>
