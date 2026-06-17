<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            (function () {
                try {
                    var raw = localStorage.getItem('dark');
                    var dark =
                        raw !== null
                            ? JSON.parse(raw)
                            : window.matchMedia &&
                              window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', !!dark);
                } catch (e) {}
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-[Inter,ui-sans-serif,system-ui,sans-serif] h-full text-gray-900 antialiased dark:text-gray-100">
        <div class="relative flex min-h-full items-center bg-gray-50 p-6 dark:bg-gray-900">
            @if(config('college.demo_mode') || session('demo_mode'))
                <div class="absolute right-16 top-4 z-10">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Demo Mode
                    </span>
                </div>
            @endif

            {{-- No Alpine here: this layout is also used outside Livewire (e.g. licence gate). --}}
            <button

                type="button"
                id="college-guest-theme-toggle"
                class="absolute right-4 top-4 z-10 rounded-md p-2 text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500/40 dark:text-purple-300"
                aria-label="{{ __('Toggle color mode') }}"
            >
                <i class="fa-solid fa-moon h-5 w-5 dark:hidden" aria-hidden="true"></i>
                <i class="fa-solid fa-sun hidden h-5 w-5 dark:inline-block" aria-hidden="true"></i>
            </button>
            <script>
                document.getElementById('college-guest-theme-toggle')?.addEventListener('click', function () {
                    var d = !document.documentElement.classList.contains('dark');
                    document.documentElement.classList.toggle('dark', d);
                    try {
                        localStorage.setItem('dark', JSON.stringify(d));
                    } catch (e) {}
                });
            </script>

            <div class="mx-auto h-full max-w-4xl flex-1 overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800">
                <div class="flex flex-col overflow-y-auto md:flex-row md:min-h-[28rem]">
                    <div class="h-32 shrink-0 md:h-auto md:w-1/2">
                        <img
                            src="{{ asset($authHeroLight ?? 'images/auth/login-office.jpeg') }}"
                            alt=""
                            aria-hidden="true"
                            class="h-full w-full object-cover dark:hidden"
                        />
                        <img
                            src="{{ asset($authHeroDark ?? 'images/auth/login-office-dark.jpeg') }}"
                            alt=""
                            aria-hidden="true"
                            class="hidden h-full w-full object-cover dark:block"
                        />
                    </div>
                    <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
                        <div class="w-full">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
