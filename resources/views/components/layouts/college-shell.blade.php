@props([
    'title' => null,
    'hideHeader' => false,
    'headerTitle' => null,
    'headerDescription' => null,
])

@php
    $path = request()->path();
    $profileRouteName = str_starts_with($path, 'admin')
        ? 'admin.profile'
        : (str_starts_with($path, 'student') ? 'student.profile' : 'teacher.profile');
    $profileUrl = \Illuminate\Support\Facades\Route::has($profileRouteName)
        ? route($profileRouteName)
        : route('profile');
    $settingsUrl = str_starts_with($path, 'admin') && \Illuminate\Support\Facades\Route::has('admin.settings.school')
        ? route('admin.settings.school')
        : null;
    $user = auth()->user();
    $displayName = $user->username ?? $user->email;
    $nameParts = preg_split('/\s+/', trim((string) ($user->name ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = $nameParts !== []
        ? mb_strtoupper(mb_substr($nameParts[0], 0, 1).(isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''))
        : mb_strtoupper(mb_substr((string) $user->email, 0, 1));

    $showDemoToggle = false;
    if ($user !== null) {
        if ($user->type === 'admin' && ($user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin')) {
            $schoolRecord = \App\Models\School::current();
            if ($schoolRecord !== null && $schoolRecord->ready) {
                $showDemoToggle = true;
            }
        }
    }
@endphp

<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        {{-- Before paint: same key / fallback as legacy `init-alpine.js` (localStorage `dark`, else prefers-color-scheme) --}}
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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @media print {
                /* Hide sidebar, top navigation header, impersonation bar, and explicitly hidden elements */
                aside, 
                header,
                .print\:hidden,
                [class*="print:hidden"],
                #impersonation-bar {
                    display: none !important;
                }

                /* Remove scrolling viewport containers so the full page height prints */
                html, body, main, .flex-1, .overflow-y-auto {
                    height: auto !important;
                    overflow: visible !important;
                    background-color: #ffffff !important;
                    color: #000000 !important;
                }

                /* Reset container layout sizing to match print sheets */
                .container,
                .mx-auto,
                .max-w-7xl,
                .max-w-5xl {
                    max-width: 100% !important;
                    width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    border: 0 !important;
                    box-shadow: none !important;
                }

                /* Standard page printing margins */
                @page {
                    margin: 1.5cm;
                }
            }
        </style>
    </head>
    <body class="h-full font-sans antialiased text-gray-900 dark:text-gray-100">
        <div
            x-data="{
                sidebarOpen: false,
                dark: false,
                notificationsOpen: false,
                profileOpen: false,
                init() {
                    this.dark = document.documentElement.classList.contains('dark');
                },
                toggleTheme() {
                    this.dark = !this.dark;
                    try {
                        localStorage.setItem('dark', JSON.stringify(this.dark));
                    } catch (e) {}
                    document.documentElement.classList.toggle('dark', this.dark);
                },
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },
                closeSidebar() {
                    this.sidebarOpen = false;
                },
                toggleNotifications() {
                    this.notificationsOpen = !this.notificationsOpen;
                    this.profileOpen = false;
                },
                closeNotifications() {
                    this.notificationsOpen = false;
                },
                toggleProfile() {
                    this.profileOpen = !this.profileOpen;
                    this.notificationsOpen = false;
                },
                closeProfile() {
                    this.profileOpen = false;
                },
            }"
            @keydown.escape.window="closeSidebar(); closeNotifications(); closeProfile();"
            :class="{ 'overflow-hidden': sidebarOpen }"
            class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900"
        >
            {{-- Sidebar: one instance; mobile sits below header (top-16) like legacy `auth_nav.php` --}}
            <aside
                :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full'"
                class="scrollbar-hidden fixed left-0 top-16 z-50 flex h-[calc(100vh-4rem)] w-64 shrink-0 flex-col overflow-y-auto border-r border-gray-100 bg-white transition-transform duration-200 ease-in-out dark:border-gray-700 dark:bg-gray-800 lg:static lg:top-auto lg:z-20 lg:h-full lg:max-h-none lg:translate-x-0"
            >
                <div class="flex min-h-0 flex-1 flex-col py-4 text-gray-500 dark:text-gray-400">
                    <a
                        href="{{ url('/') }}"
                        wire:navigate
                        class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200"
                    >
                        {{ config('app.name') }}
                    </a>
                    <div class="mt-6 min-h-0 flex-1 overflow-y-auto">
                        {{ $sidebar }}
                    </div>

                    <!-- Mobile/Tablet specific components at the bottom of the sidebar -->
                    @if($showDemoToggle)
                        <div class="md:hidden px-6 py-2.5 border-t border-gray-100 dark:border-gray-700">
                            @if(config('college.demo_mode'))
                                <span class="inline-flex w-full justify-center items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Demo Mode
                                </span>
                            @else
                                <form action="{{ route('demo.toggle') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex justify-center items-center gap-1.5 rounded-full px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-all focus-visible:outline focus-visible:outline-2 {{ session('demo_mode') ? 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-955/40 dark:text-amber-300 dark:hover:bg-amber-900/50' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-305 dark:hover:bg-gray-600' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ session('demo_mode') ? 'bg-amber-500 animate-pulse' : 'bg-green-500' }}"></span>
                                        {{ session('demo_mode') ? 'Demo Mode' : 'Live Mode' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div class="sm:hidden px-6 py-2.5 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ __('Appearance') }}</span>
                        <button
                            type="button"
                            class="rounded-md p-1.5 bg-gray-100 dark:bg-gray-700 text-gray-750 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500/40"
                            @click="toggleTheme"
                            aria-label="{{ __('Toggle color mode') }}"
                        >
                            <i class="fa-solid fa-moon h-5 w-5" x-show="!dark" x-cloak aria-hidden="true"></i>
                            <i class="fa-solid fa-sun h-5 w-5" x-show="dark" x-cloak aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="lg:hidden px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 text-sm font-semibold text-purple-800 dark:bg-purple-900/50 dark:text-purple-200">
                                {{ $initials }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-gray-450 dark:text-gray-500 font-mono">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <a
                                wire:navigate
                                href="{{ $profileUrl }}"
                                class="flex w-full items-center gap-2 rounded-md py-1.5 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
                            >
                                <i class="fa-solid fa-user-pen w-4 text-center text-gray-450" aria-hidden="true"></i>
                                <span>{{ __('Profile') }}</span>
                            </a>
                            @if ($settingsUrl)
                                <a
                                    wire:navigate
                                    href="{{ $settingsUrl }}"
                                    class="flex w-full items-center gap-2 rounded-md py-1.5 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
                                >
                                    <i class="fa-solid fa-gear w-4 text-center text-gray-450" aria-hidden="true"></i>
                                    <span>{{ __('Settings') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-auto px-6 pb-2 pt-3">
                        <livewire:layout.logout-button variant="sidebar" />
                    </div>
                </div>
            </aside>

            {{-- Mobile backdrop: full viewport; header stays above via z-index --}}
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-black/50 lg:hidden"
                style="display: none;"
                @click="closeSidebar()"
            ></div>

            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-50 bg-white py-4 shadow-md dark:bg-gray-800">
                    <div
                        class="container mx-auto flex h-full items-center justify-between px-4 text-purple-600 sm:px-6 dark:text-purple-300"
                    >
                        <button
                            type="button"
                            class="-ml-1 mr-4 rounded-md p-1 focus:outline-none focus:ring-2 focus:ring-purple-500/40 lg:hidden"
                            @click="toggleSidebar"
                            aria-label="{{ __('Menu') }}"
                        >
                            <i class="fa-solid fa-bars block h-6 w-6 text-center text-[1.1rem] leading-6" aria-hidden="true"></i>
                        </button>

                        <livewire:navigation.global-search />

                        <ul class="flex shrink-0 items-center space-x-4 sm:space-x-6">
                            @if($showDemoToggle)
                                <li class="hidden md:flex items-center">
                                    @if(config('college.demo_mode'))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Demo Mode
                                        </span>
                                    @else
                                        <form action="{{ route('demo.toggle') }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm transition-all focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 {{ session('demo_mode') ? 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ session('demo_mode') ? 'bg-amber-500 animate-pulse' : 'bg-green-500' }}"></span>
                                                {{ session('demo_mode') ? 'Demo Mode' : 'Live Mode' }}
                                            </button>
                                        </form>
                                    @endif
                                </li>
                            @endif
                            <li class="hidden sm:flex">

                                <button
                                    type="button"
                                    class="rounded-md p-1 focus:outline-none focus:ring-2 focus:ring-purple-500/40"
                                    @click="toggleTheme"
                                    aria-label="{{ __('Toggle color mode') }}"
                                >
                                    <i class="fa-solid fa-moon h-5 w-5" x-show="!dark" x-cloak aria-hidden="true"></i>
                                    <i class="fa-solid fa-sun h-5 w-5" x-show="dark" x-cloak aria-hidden="true"></i>
                                </button>
                            </li>
                            <livewire:navigation.notification-dropdown />
                            <li class="relative">
                                <button
                                    type="button"
                                    class="flex items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500/40"
                                    @click="toggleProfile"
                                    aria-label="{{ __('Account') }}"
                                    aria-haspopup="true"
                                    :aria-expanded="profileOpen"
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-xs font-semibold text-purple-800 dark:bg-purple-900/50 dark:text-purple-200"
                                    >
                                        {{ $initials }}
                                    </span>
                                </button>
                                <div
                                    x-show="profileOpen"
                                    x-transition
                                    @click.outside="closeProfile"
                                    class="absolute right-0 mt-2 w-56 space-y-2 rounded-md border border-gray-100 bg-white p-2 text-gray-600 shadow-md dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                    style="display: none;"
                                    role="menu"
                                >
                                    <div class="flex px-2 py-1">
                                        <span class="inline-flex w-full truncate text-sm" title="{{ $user->email }}">{{ $displayName }}</span>
                                    </div>
                                    <hr class="border-gray-100 dark:border-gray-600" />
                                    <a
                                        wire:navigate
                                        href="{{ $profileUrl }}"
                                        class="inline-flex w-full items-center rounded-md px-2 py-1 text-sm font-semibold transition-colors duration-150 hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                        @click="closeProfile"
                                    >
                                        <i class="fa-solid fa-user-pen mr-3 w-4 text-center" aria-hidden="true"></i>
                                        <span>{{ __('Profile') }}</span>
                                    </a>
                                    @if ($settingsUrl)
                                        <a
                                            wire:navigate
                                            href="{{ $settingsUrl }}"
                                            class="inline-flex w-full items-center rounded-md px-2 py-1 text-sm font-semibold transition-colors duration-150 hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                            @click="closeProfile"
                                        >
                                            <i class="fa-solid fa-gear mr-3 w-4 text-center" aria-hidden="true"></i>
                                            <span>{{ __('Settings') }}</span>
                                        </a>
                                    @endif
                                    <livewire:layout.logout-button variant="menu" />
                                </div>
                            </li>
                        </ul>
                    </div>
                </header>

                @if (session()->has('college_impersonator_id'))
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100 sm:px-6">
                        <span>
                            {{ __('You are viewing the site as another user. This access is recorded.') }}
                        </span>
                        <form method="post" action="{{ route('impersonation.stop') }}" class="shrink-0">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-md bg-amber-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-900 dark:bg-amber-600 dark:hover:bg-amber-500"
                            >
                                {{ __('Exit impersonation') }}
                            </button>
                        </form>
                    </div>
                @endif

                <main class="min-h-0 min-w-0 flex-1 overflow-y-auto pb-16">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 xl:px-10">
                        @isset($heading)
                            <div class="mb-4">{{ $heading }}</div>
                        @elseif (!$hideHeader && ($title || isset($headerTitle) || isset($headerDescription) || isset($headerActions)))
                            <x-college.page-header
                                :title="$headerTitle ?? $title"
                                :description="$headerDescription ?? null"
                            >
                                @isset($headerIcon)
                                    <x-slot:icon>
                                        {{ $headerIcon }}
                                    </x-slot:icon>
                                @endisset
                                @isset($headerActions)
                                    <x-slot:actions>
                                        {{ $headerActions }}
                                    </x-slot:actions>
                                @endisset
                            </x-college.page-header>
                        @endif
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <x-college.toast-stack />

        <script>
            (function() {
                const originalFetch = window.fetch;
                window.fetch = async function(...args) {
                    try {
                        const response = await originalFetch(...args);
                        if (response.status === 401 || response.status === 419) {
                            window.location.href = "{{ route('login') }}";
                            return new Response('', { status: 200 });
                        }
                        return response;
                    } catch (error) {
                        throw error;
                    }
                };
            })();
        </script>
    </body>
</html>
