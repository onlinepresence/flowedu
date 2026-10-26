@props([
    'title' => null,
    'headerTitle' => null,
    'headerDescription' => null,
])

<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/flowedu-favicon.png') }}">

        <script>
            (function () {
                try {
                    var raw = localStorage.getItem('dark');
                    var dark = raw !== null
                        ? JSON.parse(raw)
                        : window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', !!dark);
                } catch (e) {}
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-[Figtree,ui-sans-serif,system-ui,sans-serif] min-h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset('images/flowedu-logo.png') }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover">
                    <span class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ config('app.name') }}</span>
                </a>
                <button
                    type="button"
                    id="public-theme-toggle"
                    class="rounded-md p-2 text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500/40 dark:text-purple-300"
                    aria-label="{{ __('Toggle color mode') }}"
                >
                    <i class="fa-solid fa-moon dark:hidden" aria-hidden="true"></i>
                    <i class="fa-solid fa-sun hidden dark:inline-block" aria-hidden="true"></i>
                </button>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            @if ($headerTitle || $headerDescription)
                <div class="mb-6">
                    @if ($headerTitle)
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $headerTitle }}</h1>
                    @endif
                    @if ($headerDescription)
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $headerDescription }}</p>
                    @endif
                </div>
            @endif

            {{ $slot }}
        </main>

        <script>
            document.getElementById('public-theme-toggle')?.addEventListener('click', function () {
                var dark = !document.documentElement.classList.contains('dark');
                document.documentElement.classList.toggle('dark', dark);
                try {
                    localStorage.setItem('dark', JSON.stringify(dark));
                } catch (e) {}
            });
        </script>
    </body>
</html>
