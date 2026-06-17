<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name', 'College Manager') }}</title>

    <!-- Dark Mode Init -->
    <script>
        (function () {
            try {
                var raw = localStorage.getItem('dark');
                var dark = raw !== null ? JSON.parse(raw) : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', !!dark);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col justify-between transition-colors duration-200">
    
    <!-- Background Grid/Glow effect -->
    <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:14px_24px] dark:bg-[radial-gradient(circle_at_top,rgba(147,51,234,0.08),transparent_50%)] pointer-events-none"></div>

    <!-- Header Actions -->
    <header class="flex justify-between items-center px-6 py-4 w-full">
        <!-- Mini Logo -->
        <a href="{{ url('/') }}" class="flex items-center gap-2 group outline-none">
            <x-application-logo class="w-8 h-8 text-purple-600 dark:text-purple-400 group-hover:scale-105 transition-transform duration-200" />
            <span class="font-bold tracking-tight text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                {{ config('app.name', 'College Manager') }}
            </span>
        </a>

        <!-- Theme Toggle -->
        <button
            type="button"
            id="college-guest-theme-toggle"
            class="rounded-xl p-2.5 text-slate-500 hover:text-purple-600 dark:text-slate-400 dark:hover:text-purple-400 hover:bg-slate-100 dark:hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/40 transition-all"
            aria-label="{{ __('Toggle color mode') }}"
        >
            <i class="fa-solid fa-moon h-5 w-5 dark:hidden" aria-hidden="true"></i>
            <i class="fa-solid fa-sun hidden h-5 w-5 dark:inline-block" aria-hidden="true"></i>
        </button>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-xl w-full bg-white/70 dark:bg-slate-900/50 backdrop-blur-md border border-slate-200/60 dark:border-slate-800/80 shadow-xl rounded-3xl p-8 md:p-12 text-center transition-all duration-300">
            
            <!-- FontAwesome Icon Slot -->
            <div class="flex justify-center mb-6">
                <div class="relative w-24 h-24 flex items-center justify-center rounded-2xl bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400">
                    @yield('illustration')
                </div>
            </div>

            <!-- Error Code & Header -->
            <div>
                <p class="text-xs font-semibold tracking-wider text-purple-600 dark:text-purple-400 uppercase">
                    @yield('status_badge', __('HTTP Error'))
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    @yield('header_title')
                </h1>
            </div>

            <!-- Description -->
            <p class="mt-4 text-base text-slate-600 dark:text-slate-400 leading-relaxed max-w-md mx-auto">
                @yield('message')
            </p>

            <!-- Actions Panel -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                @yield('actions')
            </div>

            <!-- Troubleshooting / Tech Details (Collapsible) -->
            <details class="group mt-8 border-t border-slate-100 dark:border-slate-800/80 pt-4 text-left">
                <summary class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer select-none outline-none">
                    <span>{{ __('Troubleshooting Information') }}</span>
                    <i class="fa-solid fa-chevron-down transform group-open:rotate-180 transition-transform duration-200"></i>
                </summary>
                <div class="mt-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-900 rounded-xl p-4 text-[11px] font-mono text-slate-600 dark:text-slate-400 leading-normal overflow-x-auto scrollbar-hidden">
                    <p class="mb-1"><span class="font-semibold text-slate-800 dark:text-slate-300">Timestamp:</span> {{ now()->toIso8601String() }}</p>
                    <p class="mb-1"><span class="font-semibold text-slate-800 dark:text-slate-300">Request Path:</span> {{ request()->getRequestUri() }}</p>
                    <p class="mb-1"><span class="font-semibold text-slate-800 dark:text-slate-300">Method:</span> {{ request()->method() }}</p>
                    <p class="mb-1"><span class="font-semibold text-slate-800 dark:text-slate-300">Status Code:</span> @yield('code')</p>
                    @if(config('app.debug') && isset($exception) && $exception->getMessage())
                        <div class="mt-2 pt-2 border-t border-slate-200/50 dark:border-slate-800/60 font-semibold text-rose-600 dark:text-rose-400">
                            {{ $exception->getMessage() }}
                        </div>
                    @endif
                </div>
            </details>
        </div>
    </main>

    <!-- Footer Copyright -->
    <footer class="py-4 text-center text-xs text-slate-400 dark:text-slate-600">
        &copy; {{ date('Y') }} {{ config('app.name', 'College Manager') }}. {{ __('All rights reserved.') }}
    </footer>

    <!-- Interactive script to enable Theme Toggle -->
    <script>
        document.getElementById('college-guest-theme-toggle')?.addEventListener('click', function () {
            var d = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', d);
            try {
                localStorage.setItem('dark', JSON.stringify(d));
            } catch (e) {}
        });
    </script>
</body>
</html>
