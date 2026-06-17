<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/flowedu-favicon.png') }}">

    <title>FlowEdu — The Complete School Management System for Ghanaian Colleges</title>
    <meta name="description" content="Manage students, staff, results, finance, and internal communication — all in one place. Built specifically for Colleges of Education in Ghana.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark Mode Initializer -->
    <script>
        (function () {
            try {
                var raw = localStorage.getItem('dark');
                var dark = raw !== null ? JSON.parse(raw) : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', !!dark);
            } catch (e) {}
        })();
    </script>
</head>
<body class="font-['Plus_Jakarta_Sans',sans-serif] bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100 transition-colors duration-300">

    <!-- Theme Toggle Floating (Optional backup) -->
    <div class="fixed bottom-6 right-6 z-50">
        <button id="theme-toggle" class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 dark:focus:ring-offset-slate-950 transition transform hover:scale-105" aria-label="Toggle dark mode">
            <i class="fa-solid fa-moon text-lg dark:hidden"></i>
            <i class="fa-solid fa-sun text-lg hidden dark:inline-block"></i>
        </button>
    </div>

    <!-- Section 1 — Navigation bar (sticky) -->
    <header id="navbar" class="sticky top-0 z-40 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/80 transition-colors duration-300">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <!-- Logo -->
            <a href="#" class="flex items-center space-x-2.5 group">
                <img src="{{ asset('images/flowedu-logo.png') }}" alt="FlowEdu Logo" class="h-10 w-10 object-contain rounded-lg shadow-md group-hover:scale-105 transition-transform duration-300">
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Flow<span class="text-emerald-600 dark:text-emerald-400">Edu</span></span>
                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-medium tracking-widest uppercase">by Matme Inc</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center space-x-8 text-sm font-medium text-slate-600 dark:text-slate-300">
                <a href="#features" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Features</a>
                <a href="#portals" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Portals</a>
                <a href="#pricing" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Pricing</a>
                <a href="#faq" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">FAQ</a>
            </nav>

            <!-- Desktop Buttons -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-750 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/50 transition duration-200">
                    View demo
                </a>
                <a href="#contact" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 hover:shadow-md transition duration-200">
                    Get a quote
                </a>
            </div>

            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-btn" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 md:hidden focus:outline-none" aria-label="Toggle menu">
                <i class="fa-solid fa-bars text-xl" id="menu-icon-bars"></i>
                <i class="fa-solid fa-xmark text-xl hidden" id="menu-icon-close"></i>
            </button>
        </div>

        <!-- Mobile Drawer -->
        <div id="mobile-menu" class="hidden border-b border-slate-200 bg-white px-6 py-6 dark:border-slate-800 dark:bg-slate-900 md:hidden animate-fade-in transition-all duration-300">
            <nav class="flex flex-col space-y-4 text-base font-semibold text-slate-700 dark:text-slate-300">
                <a href="#features" class="mobile-nav-link hover:text-emerald-600 dark:hover:text-emerald-400">Features</a>
                <a href="#portals" class="mobile-nav-link hover:text-emerald-600 dark:hover:text-emerald-400">Portals</a>
                <a href="#pricing" class="mobile-nav-link hover:text-emerald-600 dark:hover:text-emerald-400">Pricing</a>
                <a href="#faq" class="mobile-nav-link hover:text-emerald-600 dark:hover:text-emerald-400">FAQ</a>
                <hr class="border-slate-200 dark:border-slate-800">
                <div class="flex flex-col space-y-3 pt-2">
                    <a href="{{ route('login') }}" class="flex justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/50">
                        View demo
                    </a>
                    <a href="#contact" class="mobile-nav-link flex justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500">
                        Get a quote
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Section 2 — Hero -->
    <section class="relative overflow-hidden pt-12 pb-24 lg:pt-20 lg:pb-32 dark:bg-slate-950 transition-colors duration-300">
        <!-- Abstract background glow elements -->
        <div class="absolute top-1/4 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/10 blur-[100px] dark:bg-emerald-500/5"></div>
        <div class="absolute top-1/3 right-10 -z-10 h-96 w-96 rounded-full bg-emerald-600/5 blur-[120px] dark:bg-emerald-500/5"></div>

        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-8">
                <!-- Text Content -->
                <div class="space-y-8 lg:col-span-6 text-center lg:text-left">
                    <div class="inline-flex items-center space-x-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 ring-1 ring-inset ring-emerald-600/10">
                        <span>🚀 Launching FlowEdu</span>
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl font-['Outfit'] leading-tight">
                        The complete school management system built for <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500 dark:from-emerald-400 dark:to-teal-300">Ghanaian colleges</span>
                    </h1>
                    <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Manage students, staff, results, finance, and internal communication — all in one place. Built specifically for Colleges of Education in Ghana, tailored to local policies and grading systems.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ route('login') }}" class="w-full sm:w-auto flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 hover:shadow-xl hover:shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-circle-play mr-2"></i> View live demo
                        </a>
                        <a href="#pricing" class="w-full sm:w-auto flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3.5 text-base font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition transform hover:-translate-y-0.5">
                            See pricing
                        </a>
                    </div>
                </div>

                <!-- Dashboard Interactive Mockup -->
                <div class="lg:col-span-6 relative">
                    <div class="relative mx-auto w-full max-w-md sm:max-w-xl lg:max-w-none rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                        <!-- Browser Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 dark:border-slate-850">
                            <div class="flex space-x-1.5">
                                <span class="h-3 w-3 rounded-full bg-red-400"></span>
                                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                                <span class="h-3 w-3 rounded-full bg-green-400"></span>
                            </div>
                            <div class="rounded bg-slate-100 px-3 sm:px-12 py-1 text-[10px] text-slate-400 dark:bg-slate-800 dark:text-slate-500 font-mono truncate max-w-[150px] sm:max-w-none">
                                {{ request()->getHttpHost() }}/admin/dashboard
                            </div>
                            <div class="w-10"></div>
                        </div>

                        <!-- Mockup Body (Admin Dashboard) -->
                        <div class="grid grid-cols-12 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 h-64 sm:h-96 rounded-b-xl overflow-hidden font-sans">
                            <!-- Sidebar -->
                            <div class="hidden md:flex md:col-span-3 border-r border-slate-200 bg-white p-3 dark:border-slate-850 dark:bg-slate-900 flex-col space-y-2">
                                <div class="h-6 w-full rounded bg-slate-200 dark:bg-slate-850 mb-2 animate-pulse"></div>
                                <div class="space-y-1.5 flex-1">
                                    <div class="h-4 w-4/5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 p-1 flex items-center"><span class="h-1.5 w-full rounded bg-emerald-600"></span></div>
                                    <div class="h-4 w-3/4 rounded bg-slate-100 dark:bg-slate-850"></div>
                                    <div class="h-4 w-4/5 rounded bg-slate-100 dark:bg-slate-850"></div>
                                    <div class="h-4 w-2/3 rounded bg-slate-100 dark:bg-slate-850"></div>
                                    <div class="h-4 w-3/4 rounded bg-slate-100 dark:bg-slate-850"></div>
                                </div>
                            </div>
                            <!-- Content -->
                            <div class="col-span-12 md:col-span-9 p-4 space-y-4 overflow-y-auto">
                                <!-- Top Info -->
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="h-4 w-32 rounded bg-slate-300 dark:bg-slate-800"></div>
                                        <div class="h-3 w-48 rounded bg-slate-200 dark:bg-slate-850"></div>
                                    </div>
                                    <div class="h-7 w-20 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">Active</div>
                                </div>
                                <!-- Grid Stats -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 dark:border-slate-850 dark:bg-slate-900 space-y-2">
                                        <div class="h-3 w-10 rounded bg-slate-200 dark:bg-slate-850"></div>
                                        <div class="h-5 w-16 rounded bg-emerald-600/20 text-emerald-600 font-bold text-sm flex items-center px-1"><span class="hidden sm:inline">1,248</span></div>
                                        <div class="h-2 w-full rounded bg-slate-100 dark:bg-slate-850"></div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 dark:border-slate-850 dark:bg-slate-900 space-y-2">
                                        <div class="h-3 w-10 rounded bg-slate-200 dark:bg-slate-850"></div>
                                        <div class="h-5 w-16 rounded bg-blue-600/20 text-blue-600 font-bold text-sm flex items-center px-1"><span class="hidden sm:inline">84</span></div>
                                        <div class="h-2 w-full rounded bg-slate-100 dark:bg-slate-850"></div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 dark:border-slate-850 dark:bg-slate-900 space-y-2">
                                        <div class="h-3 w-10 rounded bg-slate-200 dark:bg-slate-850"></div>
                                        <div class="h-5 w-16 rounded bg-purple-600/20 text-purple-600 font-bold text-sm flex items-center px-1"><span class="hidden sm:inline">94K</span></div>
                                        <div class="h-2 w-full rounded bg-slate-100 dark:bg-slate-850"></div>
                                    </div>
                                </div>
                                <!-- Large table mockup -->
                                <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-850 dark:bg-slate-900 space-y-2">
                                    <div class="h-4.5 w-24 rounded bg-slate-200 dark:bg-slate-850"></div>
                                    <div class="space-y-1.5 pt-1">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 dark:border-slate-850">
                                            <div class="h-3.5 w-24 rounded bg-slate-100 dark:bg-slate-850"></div>
                                            <div class="h-3.5 w-12 rounded bg-emerald-500/10"></div>
                                        </div>
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 dark:border-slate-850">
                                            <div class="h-3.5 w-28 rounded bg-slate-100 dark:bg-slate-850"></div>
                                            <div class="h-3.5 w-12 rounded bg-red-500/10"></div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="h-3.5 w-20 rounded bg-slate-100 dark:bg-slate-850"></div>
                                            <div class="h-3.5 w-12 rounded bg-emerald-500/10"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3 — Social proof bar -->
    <section class="border-y border-slate-200 bg-white py-8 dark:border-slate-850 dark:bg-slate-900 transition-colors duration-300">
        <div class="mx-auto max-w-7xl px-6 text-center space-y-4">
            <p class="text-xs font-bold tracking-widest text-emerald-600 dark:text-emerald-400 uppercase">
                COMMITTED TO RELIABILITY & LOCAL INTEGRATION
            </p>
            <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-12 text-sm font-semibold text-slate-500 dark:text-slate-400">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-address-card text-emerald-500"></i>
                    <span>Ghana Card validation</span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-cedi-sign text-emerald-500"></i>
                    <span>GHS Currency Native</span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-headset text-emerald-500"></i>
                    <span>24/7 Local Support</span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                    <span>Full Institutional Control</span>
                </div>
            </div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                FlowEdu — serving educational administration systems since May 2026
            </div>
        </div>
    </section>

    <!-- Section 4 — The three portals -->
    <section id="portals" class="py-24 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <div class="mx-auto max-w-7xl px-6">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl font-['Outfit']">
                    A unified platform with three tailored portals
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    Different interfaces for different roles, working together in real-time. Full synchrony between administrators, lecturers, and students.
                </p>
            </div>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Admin Portal Card -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-8 shadow-sm hover:shadow-md hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 transition duration-300 group">
                    <div class="space-y-6">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-450 shadow-sm group-hover:scale-110 transition duration-300">
                            <i class="fa-solid fa-shield-halved text-xl"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Admin Portal</h3>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Full institutional control</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                Complete back-office capabilities to orchestrate operations, manage files, structure departments, and audit records.
                            </p>
                        </div>
                        <ul class="space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800 text-sm text-slate-600 dark:text-slate-350">
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-emerald-500 text-xs"></i>
                                <span>Manage student lifecycle & admissions</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-emerald-500 text-xs"></i>
                                <span>Record, verify, and approve grading</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-emerald-500 text-xs"></i>
                                <span>Award scholarships, track disbursements & bulk-allocate allowances</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-emerald-500 text-xs"></i>
                                <span>Compose, sign, and route internal memos</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-emerald-500 text-xs"></i>
                                <span>Role-based access controls for staff</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Teacher Portal Card -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-8 shadow-sm hover:shadow-md hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 transition duration-300 group">
                    <div class="space-y-6">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-950 dark:text-teal-450 shadow-sm group-hover:scale-110 transition duration-300">
                            <i class="fa-solid fa-chalkboard-user text-xl"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Teacher Portal</h3>
                            <p class="text-xs font-semibold text-teal-600 dark:text-teal-400">Everything a lecturer needs</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                Empowerment tools for lecturers to handle classes, student assessments, marks submissions, and lecture tracking.
                            </p>
                        </div>
                        <ul class="space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800 text-sm text-slate-600 dark:text-slate-350">
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-teal-500 text-xs"></i>
                                <span>View assigned courses & rosters</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-teal-500 text-xs"></i>
                                <span>Track and record student attendance</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-teal-500 text-xs"></i>
                                <span>Excel bulk results upload tool</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-teal-500 text-xs"></i>
                                <span>Publish announcement updates direct to students</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Student Portal Card -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-8 shadow-sm hover:shadow-md hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 transition duration-300 group">
                    <div class="space-y-6">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-450 shadow-sm group-hover:scale-110 transition duration-300">
                            <i class="fa-solid fa-graduation-cap text-xl"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Student Portal</h3>
                            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">Students access everything online</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                Self-service tools for students to monitor grades, check financial balances, verify clearance, and submit feedback.
                            </p>
                        </div>
                        <ul class="space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800 text-sm text-slate-600 dark:text-slate-350">
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                <span>Check grades, GPA & academic transcripts</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                <span>Track fee bills, records & receipts</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                <span>Submit departmental clearance checklists</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                <span>Complete anonymous lecturer evaluations</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                <span>Track monthly allowances, grants & stipends</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                <span>Access campus career opportunities & registered activities</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5 — Feature highlights (alternating layout) -->
    <section id="features" class="py-24 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-850 transition-colors duration-300">
        <div class="mx-auto max-w-7xl px-6 space-y-32">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl font-['Outfit']">
                    Advanced Modules Designed for Modern Administration
                </h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm">
                    Examine our workflows below to see how we digitize previously tedious manual processes.
                </p>
            </div>

            <!-- Block 1 — Results & Grading -->
            <div class="grid items-center gap-12 lg:grid-cols-12">
                <div class="lg:col-span-6 space-y-6">
                    <div class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-450">
                        Results & Grading
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl font-['Outfit']">
                        From result entry to official transcript in minutes
                    </h3>
                    <p class="text-slate-600 dark:text-slate-350 leading-relaxed text-sm">
                        Say goodbye to complex calculations and Excel templates that break on upload. Our system reads direct grades, processes GPA/CGPA scores, manages approvals, and issues printable transcripts instantly.
                    </p>
                    <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-emerald-500"></i><span>Bulk uploading of grades via formatted spreadsheet</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-emerald-500"></i><span>Automatic GPA calculation and class designation</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-emerald-500"></i><span>Academic board approval chain workflow</span></li>
                    </ul>
                </div>
                <div class="lg:col-span-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-950/60 shadow-sm flex flex-col space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                            <span class="font-bold text-sm text-slate-700 dark:text-slate-300"><i class="fa-solid fa-file-invoice mr-2 text-emerald-500"></i>Academic Transcript View</span>
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-850 dark:bg-emerald-950 dark:text-emerald-400 font-bold">Approved</span>
                        </div>
                        <div class="space-y-2.5">
                            <div class="grid grid-cols-4 gap-2 text-xs font-semibold text-slate-400">
                                <span>Course</span><span class="text-center">Credit</span><span class="text-center">Grade</span><span class="text-right">GP</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-xs border-b border-slate-100 pb-1.5 dark:border-slate-850">
                                <span>EBS 102 General English</span><span class="text-center">3.0</span><span class="text-center font-bold text-slate-800 dark:text-slate-200">A</span><span class="text-right font-mono">12.0</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-xs border-b border-slate-100 pb-1.5 dark:border-slate-850">
                                <span>EBS 104 Algebra & Geom.</span><span class="text-center">3.0</span><span class="text-center font-bold text-slate-800 dark:text-slate-200">B+</span><span class="text-right font-mono">10.5</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-xs">
                                <span>EBS 106 Education Dev.</span><span class="text-center">2.0</span><span class="text-center font-bold text-slate-800 dark:text-slate-200">A</span><span class="text-right font-mono">8.0</span>
                            </div>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-3 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200">
                            <span>GPA / CGPA Total:</span>
                            <span class="font-mono text-emerald-650 dark:text-emerald-450">3.81 (First Class Division)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Block 2 — Finance -->
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:flex-row-reverse">
                <div class="lg:col-span-6 lg:order-2 space-y-6">
                    <div class="inline-flex items-center rounded-lg bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700 dark:bg-teal-950/40 dark:text-teal-455">
                        Financial Administration
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl font-['Outfit']">
                        Stop chasing fees with complex spreadsheets
                    </h3>
                    <p class="text-slate-600 dark:text-slate-350 leading-relaxed text-sm">
                        Create transparent and automated ledger entries. Assign personalized billing profiles based on year, program, and residential status. Collect, record, and track transactions with live logs.
                    </p>
                    <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-teal-500"></i><span>Define dynamic fee structures for cohorts</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-teal-500"></i><span>Searchable outstanding student balances list</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-teal-500"></i><span>Record scholarship deductions and payment logs</span></li>
                    </ul>
                </div>
                <div class="lg:col-span-6 lg:order-1">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-950/60 shadow-sm flex flex-col space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                            <span class="font-bold text-sm text-slate-700 dark:text-slate-300"><i class="fa-solid fa-wallet mr-2 text-teal-500"></i>Fee Collection Tracker</span>
                            <span class="text-xs text-slate-450 font-mono">FY 2025/2026</span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Total Billed Fees:</span>
                                <span class="font-bold font-mono text-slate-850 dark:text-slate-200">GHS 842,500.00</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Total Collected:</span>
                                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">GHS 612,180.00</span>
                            </div>
                            <!-- Simple SVG/CSS Chart Bar -->
                            <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                                <div class="bg-emerald-500 h-3 rounded-full" style="width: 72.6%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>Collected (72.6%)</span>
                                <span>Outstanding: GHS 230,320.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Block 3 — Internal Memos -->
            <div class="grid items-center gap-12 lg:grid-cols-12">
                <div class="lg:col-span-6 space-y-6">
                    <div class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/40 dark:text-blue-450">
                        Internal Communication
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl font-['Outfit']">
                        Replace paper memos with a digital trail
                    </h3>
                    <p class="text-slate-600 dark:text-slate-350 leading-relaxed text-sm">
                        Speed up administrative decisions. Compose formal memos, routing lists, and announcements digitally inside the system. Review chains track who viewed the document, who signed it, and when.
                    </p>
                    <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-blue-500"></i><span>Compose, review, and finalize formal digital memos</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-blue-500"></i><span>Track routing and view logs in real-time</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-blue-500"></i><span>Internal announcement board directly linked to portal notifications</span></li>
                    </ul>
                </div>
                <div class="lg:col-span-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-950/60 shadow-sm flex flex-col space-y-4">
                        <div class="border-b border-slate-200 pb-3 dark:border-slate-800 flex justify-between items-center">
                            <span class="font-bold text-sm text-slate-700 dark:text-slate-300"><i class="fa-solid fa-envelope-open-text mr-2 text-blue-500"></i>Digital Routing Audit</span>
                            <span class="rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-800 dark:bg-blue-950 dark:text-blue-400 font-semibold font-mono text-[10px]">Ref: SMS-M26</span>
                        </div>
                        <div class="space-y-3.5 text-xs">
                            <div class="flex items-start space-x-3">
                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400"><i class="fa-solid fa-check text-[10px]"></i></div>
                                <div>
                                    <p class="font-bold">Principal Office (Dr. K. Mensah)</p>
                                    <p class="text-[10px] text-slate-400">Approved & signed • May 25, 2026 (14:32 GMT)</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400"><i class="fa-solid fa-check text-[10px]"></i></div>
                                <div>
                                    <p class="font-bold">Registrar Office (A. Boateng)</p>
                                    <p class="text-[10px] text-slate-400">Approved & signed • May 25, 2026 (11:15 GMT)</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-850 dark:bg-blue-950 dark:text-blue-400"><i class="fa-solid fa-eye text-[10px]"></i></div>
                                <div>
                                    <p class="font-bold">Vice Principal (H. Owusu)</p>
                                    <p class="text-[10px] text-slate-400">Viewed, signature pending • May 25, 2026 (09:40 GMT)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Block 4 — Teacher Evaluations -->
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:flex-row-reverse">
                <div class="lg:col-span-6 lg:order-2 space-y-6">
                    <div class="inline-flex items-center rounded-lg bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-455">
                        Performance Analytics
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl font-['Outfit']">
                        Structured feedback, every single semester
                    </h3>
                    <p class="text-slate-600 dark:text-slate-350 leading-relaxed text-sm">
                        Build responsive evaluation templates and assign them to semesters. Students submit anonymous ratings through their portals. Admins gain detailed analytics to improve educational standards.
                    </p>
                    <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-purple-550"></i><span>Create customized multi-choice evaluation questionnaires</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-purple-550"></i><span>100% anonymous submissions to protect privacy</span></li>
                        <li class="flex items-center space-x-2"><i class="fa-solid fa-check text-purple-550"></i><span>Interactive dashboard charts per course and lecturer</span></li>
                    </ul>
                </div>
                <div class="lg:col-span-6 lg:order-1">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-950/60 shadow-sm flex flex-col space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                            <span class="font-bold text-sm text-slate-700 dark:text-slate-300"><i class="fa-solid fa-chart-line mr-2 text-purple-500"></i>Lecturer Evaluation Summary</span>
                            <span class="text-xs text-slate-400">Sem 1, 2026</span>
                        </div>
                        <div class="space-y-3.5 text-xs">
                            <div class="space-y-1">
                                <div class="flex justify-between font-semibold">
                                    <span>Subject Knowledge / Preparation</span>
                                    <span class="font-bold text-purple-650 dark:text-purple-400">4.8 / 5.0</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: 96%"></div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex justify-between font-semibold">
                                    <span>Communication & Clarity</span>
                                    <span class="font-bold text-purple-650 dark:text-purple-400">4.3 / 5.0</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: 86%"></div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex justify-between font-semibold">
                                    <span>Punctuality & Availability</span>
                                    <span class="font-bold text-purple-650 dark:text-purple-400">4.6 / 5.0</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: 92%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6 — Pricing (Interactive) -->
    <section id="pricing" class="py-24 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <div class="mx-auto max-w-7xl px-6">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">SIMPLE, TRANSPARENT BILLING</span>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl font-['Outfit']">
                    Flexible Pricing for Every Institution Size
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    Select your student population size, then toggle the add-on modules and implementation packages below to build your customized FlowEdu quotation.
                </p>
            </div>

            <!-- Band Selector Container -->
            <div class="mx-auto max-w-2xl text-center mb-12">
                <label for="student-band-select-mobile" class="block text-sm font-bold text-slate-700 dark:text-slate-355 mb-3">
                    Step 1: Choose Your Active Student Population
                </label>
                <!-- Segmented Control (Desktop) & Select (Mobile) -->
                <div class="relative hidden sm:inline-flex rounded-xl bg-slate-200/60 p-1 dark:bg-slate-900 border border-slate-300/40 dark:border-slate-800">
                    <button type="button" class="band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200" data-band="1-500">1 – 500 Students</button>
                    <button type="button" class="band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200" data-band="501-1000">501 – 1,000 Students</button>
                    <button type="button" class="band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200" data-band="1001-2000">1,001 – 2,000 Students</button>
                    <button type="button" class="band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200" data-band="2001-3500">2,001 – 3,500 Students</button>
                    <button type="button" class="band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200" data-band="3500+">3,500+ Students</button>
                </div>
                <div class="sm:hidden w-full px-4">
                    <select id="student-band-select-mobile" class="w-full rounded-xl border-slate-300 bg-white py-2.5 text-sm font-semibold dark:border-slate-800 dark:bg-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="1-500">1 – 500 Students</option>
                        <option value="501-1000">501 – 1,000 Students</option>
                        <option value="1001-2000">1,001 – 2,000 Students</option>
                        <option value="2001-3500">2,001 – 3,500 Students</option>
                        <option value="3500+">3,500+ Students (Custom Quote)</option>
                    </select>
                </div>
            </div>

            <!-- Custom Quote Notification View -->
            <div id="custom-quote-view" class="hidden mx-auto max-w-xl rounded-2xl border border-dashed border-emerald-500/50 bg-emerald-50/50 p-8 text-center space-y-4 dark:bg-slate-900/40">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><i class="fa-solid fa-envelope text-xl"></i></div>
                <h3 class="text-lg font-bold">Custom Quote Required</h3>
                <p class="text-sm text-slate-500">For institutions larger than 3,500 active students, we provide personalized hosting capacities, load-balanced servers, and customized training frameworks.</p>
                <a href="#contact" class="inline-flex rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow hover:bg-emerald-500">Request Custom Quote</a>
            </div>

            <!-- Calculator Grid -->
            <div id="calculator-grid" class="grid gap-8 lg:grid-cols-12 items-start">
                <!-- Modules & Core Selection -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Core Card (Locked) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-4 gap-2">
                            <div>
                                <span class="rounded-md bg-emerald-150 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400 tracking-wider uppercase">Always Included</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1">Core Academic Licence</h3>
                                <p class="text-xs text-slate-500 font-medium">Complete core package containing academic structure, grading tools, and portals.</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <span id="core-annual-price" class="text-2xl font-black font-mono text-slate-900 dark:text-white">GHS 4,500.00</span>
                                <span class="text-xs text-slate-500 block">Upfront (Year 1)</span>
                                <span id="core-renew-price-label" class="text-[10px] text-slate-400 block font-semibold">Annual Renewal: GHS 1,200.00 / yr</span>
                            </div>
                        </div>

                        <!-- Included Core features list -->
                        <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center space-x-2"><i class="fa-solid fa-lock text-emerald-500"></i><span>Academic Structure Manager</span></div>
                            <div class="flex items-center space-x-2"><i class="fa-solid fa-lock text-emerald-500"></i><span>Student Management Profile</span></div>
                            <div class="flex items-center space-x-2"><i class="fa-solid fa-lock text-emerald-500"></i><span>Results Upload & Slip Issuer</span></div>
                            <div class="flex items-center space-x-2"><i class="fa-solid fa-lock text-emerald-500"></i><span>Lecturer Course Dashboard</span></div>
                            <div class="flex items-center space-x-2"><i class="fa-solid fa-lock text-emerald-500"></i><span>Student Course & Transcript Portal</span></div>
                            <div class="flex items-center space-x-2"><i class="fa-solid fa-lock text-emerald-500"></i><span>Lecture Attendance Tracker</span></div>
                        </div>
                    </div>

                    <!-- Step 2 Header -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-300 mb-3">Step 2: Choose Add-on Modules</h4>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach(config('licence.modules') as $key => $module)
                                <label for="module-chk-{{ $key }}" class="module-card relative flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 cursor-pointer shadow-sm select-none hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                                    <div class="flex items-start justify-between space-x-2 mb-3">
                                        <div class="space-y-1">
                                            <span class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                {{ $module['label'] }}
                                                @if($key === 'finance' || $key === 'student_welfare')
                                                    <span class="rounded bg-teal-100 px-1.5 py-0.5 text-[9px] font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-400">Popular</span>
                                                @endif
                                            </span>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ $module['description'] }}</p>
                                        </div>
                                        <input type="checkbox" id="module-chk-{{ $key }}" value="{{ $key }}" class="module-checkbox h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-850 dark:bg-slate-950">
                                    </div>
                                    <div class="border-t border-slate-100 dark:border-slate-850 pt-2 flex items-center justify-between text-[11px] font-medium text-slate-400 font-mono">
                                        <span>One-time Addition:</span>
                                        <span class="module-onetime text-slate-800 dark:text-slate-300 font-bold" data-base="{{ $module['base_price'] }}">GHS 0.00</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono pt-1">
                                        <span>Renewal slice:</span>
                                        <span class="module-renew" data-base-renew="{{ $module['renewal_base'] }}">GHS 0.00 / year</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Step 3: Setup & Implementation options -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-350 mt-8 mb-3">Step 3: Setup & Implementation Options</h4>
                        <div class="space-y-4">
                            <!-- Hosting setup selection -->
                            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 space-y-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 block">Server & Hosting Setup</span>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <label class="hosting-option relative flex items-center space-x-2 border rounded-lg p-3 bg-slate-50 dark:bg-slate-950 cursor-pointer select-none border-emerald-500/35">
                                        <input type="radio" name="hosting_setup" value="self_hosted" checked class="text-emerald-650 focus:ring-emerald-500">
                                        <div class="text-xs">
                                            <span class="font-bold block">Self-Hosted Server</span>
                                            <span class="text-[10px] text-slate-400 block font-mono">GHS 1,200.00</span>
                                        </div>
                                    </label>
                                    <label class="hosting-option relative flex items-center space-x-2 border rounded-lg p-3 bg-slate-50 dark:bg-slate-950 cursor-pointer select-none">
                                        <input type="radio" name="hosting_setup" value="managed" class="text-emerald-650 focus:ring-emerald-500">
                                        <div class="text-xs">
                                            <span class="font-bold block">Managed Cloud</span>
                                            <span class="text-[10px] text-slate-400 block font-mono">GHS 1,600.00</span>
                                        </div>
                                    </label>
                                    <label class="hosting-option relative flex items-center space-x-2 border rounded-lg p-3 bg-slate-50 dark:bg-slate-950 cursor-pointer select-none">
                                        <input type="radio" name="hosting_setup" value="none" class="text-emerald-650 focus:ring-emerald-500">
                                        <div class="text-xs">
                                            <span class="font-bold block">Self-Install</span>
                                            <span class="text-[10px] text-slate-400 block font-mono">None (GHS 0.00)</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Additional options checkboxes -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label for="config-setup-chk" class="impl-addon flex items-start space-x-3 rounded-xl border border-slate-200 bg-white p-4 cursor-pointer dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                                    <input type="checkbox" id="config-setup-chk" value="800" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="text-xs font-bold block">System Configuration & Data Entry (+ GHS 800.00)</span>
                                        <span class="text-[10px] text-slate-500 leading-relaxed block">Setup of programmes, courses, departments, academic sessions.</span>
                                    </div>
                                </label>
                                <label for="migration-chk" class="impl-addon flex items-start space-x-3 rounded-xl border border-slate-200 bg-white p-4 cursor-pointer dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                                    <input type="checkbox" id="migration-chk" value="2000" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="text-xs font-bold block">Legacy Data Migration (+ GHS 2,000.00)</span>
                                        <span class="text-[10px] text-slate-500 leading-relaxed block">Full migration of students, grades, history from legacy software.</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Training quantity options -->
                            <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 block mb-3">Staff Training & Workshops</span>
                                <div class="grid gap-4 sm:grid-cols-3 text-xs">
                                    <div class="space-y-1.5">
                                        <label for="admin-train-qty" class="font-bold text-slate-700 dark:text-slate-300">Remote Admin Training</label>
                                        <p class="text-[9px] text-slate-400 leading-none">GHS 600.00 / session</p>
                                        <select id="admin-train-qty" class="w-full rounded border-slate-300 dark:border-slate-850 dark:bg-slate-950 py-1.5 focus:ring-emerald-500">
                                            <option value="0">None</option>
                                            <option value="1">1 Session (GHS 600)</option>
                                            <option value="2">2 Sessions (GHS 1,200)</option>
                                            <option value="3">3 Sessions (GHS 1,800)</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="teacher-train-qty" class="font-bold text-slate-700 dark:text-slate-300">Remote Lecturer Training</label>
                                        <p class="text-[9px] text-slate-400 leading-none">GHS 500.00 / session</p>
                                        <select id="teacher-train-qty" class="w-full rounded border-slate-300 dark:border-slate-850 dark:bg-slate-950 py-1.5 focus:ring-emerald-500">
                                            <option value="0">None</option>
                                            <option value="1">1 Session (GHS 500)</option>
                                            <option value="2">2 Sessions (GHS 1,000)</option>
                                            <option value="3">3 Sessions (GHS 1,500)</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="onsite-train-qty" class="font-bold text-slate-700 dark:text-slate-300">On-Site Training Days</label>
                                        <p class="text-[9px] text-slate-400 leading-none">GHS 1,500.00 / day</p>
                                        <select id="onsite-train-qty" class="w-full rounded border-slate-300 dark:border-slate-850 dark:bg-slate-950 py-1.5 focus:ring-emerald-500">
                                            <option value="0">None</option>
                                            <option value="1">1 Day (GHS 1,500)</option>
                                            <option value="2">2 Days (GHS 3,000)</option>
                                            <option value="3">3 Days (GHS 4,500)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Running Total Panel -->
                <div class="lg:col-span-4 lg:sticky lg:top-24">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md dark:border-slate-800 dark:bg-slate-900 space-y-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-calculator text-emerald-500"></i>
                            Projected Summary
                        </h3>

                        <!-- Selected Count -->
                        <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                            <span>Licence & Modules:</span>
                            <span id="selected-summary-count" class="font-bold text-slate-850 dark:text-slate-250">Core + 0 modules</span>
                        </div>

                        <!-- Pricing Breakdown -->
                        <div class="space-y-3 border-y border-slate-100 py-4 dark:border-slate-800 text-xs">
                            <div class="flex justify-between font-mono">
                                <span>Core upfront licence:</span>
                                <span id="summary-core-annual">GHS 0.00</span>
                            </div>
                            <div class="flex justify-between font-mono">
                                <span>Core renewal licence:</span>
                                <span id="summary-core-renew">GHS 0.00</span>
                            </div>
                            <div class="flex justify-between font-mono">
                                <span>Modules upfront total:</span>
                                <span id="summary-modules-onetime">GHS 0.00</span>
                            </div>
                            <div class="flex justify-between font-mono">
                                <span>Modules renewal total:</span>
                                <span id="summary-modules-renew">GHS 0.00</span>
                            </div>

                            <!-- Setup & Training items -->
                            <div class="flex justify-between font-mono">
                                <span>Hosting & Setup fee:</span>
                                <span id="summary-impl-setup">GHS 0.00</span>
                            </div>
                            <div class="flex justify-between font-mono">
                                <span>Training & Data migration:</span>
                                <span id="summary-impl-training">GHS 0.00</span>
                            </div>

                            <!-- Bundle Discount Row -->
                            <div id="bundle-discount-row" class="hidden flex justify-between font-semibold text-emerald-600 dark:text-emerald-450">
                                <span>Bundle Discount (12%):</span>
                                <span id="summary-discount" class="font-mono">-GHS 0.00</span>
                            </div>

                            <!-- Founding Client Discount Row -->
                            <div id="founding-discount-row" class="hidden flex justify-between font-semibold text-emerald-600 dark:text-emerald-450">
                                <span>Founding Discount (15%):</span>
                                <span id="summary-founding-discount" class="font-mono">-GHS 0.00</span>
                            </div>
                        </div>

                        <!-- Founding Client Offer Checkbox -->
                        <div class="flex items-start space-x-2.5 bg-slate-50 dark:bg-slate-950 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                            <input type="checkbox" id="founding-client-chk" class="h-4.5 w-4.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <div>
                                <label for="founding-client-chk" class="text-xs font-bold text-slate-800 dark:text-slate-250 cursor-pointer">Apply Founding Discount</label>
                                <p class="text-[10px] text-slate-550 leading-tight">Get 15% off the Core licence fee. Requires testimonial & reference status.</p>
                            </div>
                        </div>

                        <!-- Grand Totals -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white block">One-time Total (Year 1)</span>
                                    <span class="text-[10px] text-slate-400">Upfront licence, installation, config, training, and modules.</span>
                                </div>
                                <span id="total-upfront-price" class="text-2xl font-black font-mono text-slate-900 dark:text-white">GHS 0.00</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                                <div>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white block">Annual Renewal (Year 2+)</span>
                                    <span class="text-[10px] text-slate-400">Recurring Core licence and module renewals.</span>
                                </div>
                                <span id="total-renew-price" class="text-lg font-bold font-mono text-emerald-650 dark:text-emerald-450">GHS 0.00 / year</span>
                            </div>
                        </div>

                        <!-- Bundle Alert Banner -->
                        <div id="bundle-alert-banner" class="hidden rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-500/20 font-semibold animate-pulse">
                            🎉 Bundle discount applied — 12% off module total!
                        </div>

                        <button type="button" id="quote-prefill-btn" class="w-full rounded-xl bg-emerald-600 py-3 text-center text-sm font-bold text-white shadow-md hover:bg-emerald-500 transition duration-200 transform active:scale-95">
                            Get a quote for this configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 7 — Demo callout -->
    <section class="bg-gradient-to-r from-emerald-700 to-emerald-600 py-16 text-white text-center transition-colors duration-300">
        <div class="mx-auto max-w-4xl px-6 space-y-6">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl font-['Outfit']">See FlowEdu in Action</h2>
            <p class="text-emerald-105 leading-relaxed text-sm max-w-2xl mx-auto">
                Log in to our demo environment and explore a fully working system with sample data. Admin, teacher, and student accounts are all available.
            </p>
            <div class="pt-2">
                <a href="{{ route('login') }}" class="inline-flex rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-emerald-800 shadow-md hover:bg-slate-50 transition transform hover:-translate-y-0.5">
                    Open demo environment
                </a>
            </div>
            <p class="text-xs text-emerald-200">
                Demo data resets periodically. Nothing you enter is saved permanently.
            </p>
        </div>
    </section>

    <!-- Section 8 — FAQ -->
    <section id="faq" class="py-24 bg-white dark:bg-slate-900 transition-colors duration-300">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center space-y-4 mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">FAQS</span>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl font-['Outfit']">
                    Frequently Asked Questions
                </h2>
                <p class="text-slate-500">
                    Find quick answers to common licensing, integration, and training inquiries.
                </p>
            </div>

            <!-- FAQ Accordion List -->
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                <!-- FAQ 1 -->
                <div class="faq-item py-5">
                    <button type="button" class="faq-trigger flex w-full items-center justify-between text-left focus:outline-none">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Where is our data stored?</span>
                        <span class="faq-icon ml-6 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-550 dark:bg-slate-800"><i class="fa-solid fa-plus text-xs"></i></span>
                    </button>
                    <div class="faq-content hidden mt-3 max-h-0 overflow-hidden transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed dark:text-slate-400">
                            The system is installed on your own server, which means your student data never leaves your institution. You have full control over backups, database access, and data privacy policies.
                        </p>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="faq-item py-5">
                    <button type="button" class="faq-trigger flex w-full items-center justify-between text-left focus:outline-none">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Do we need internet to use it?</span>
                        <span class="faq-icon ml-6 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-550 dark:bg-slate-800"><i class="fa-solid fa-plus text-xs"></i></span>
                    </button>
                    <div class="faq-content hidden mt-3 max-h-0 overflow-hidden transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed dark:text-slate-400">
                            The system runs on your local network (LAN). If your server is hosted on-campus, staff and teachers can access it locally without active internet. Active internet is only needed if you want students or staff to log in from outside the campus.
                        </p>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="faq-item py-5">
                    <button type="button" class="faq-trigger flex w-full items-center justify-between text-left focus:outline-none">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">What happens if we do not renew?</span>
                        <span class="faq-icon ml-6 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-550 dark:bg-slate-800"><i class="fa-solid fa-plus text-xs"></i></span>
                    </button>
                    <div class="faq-content hidden mt-3 max-h-0 overflow-hidden transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed dark:text-slate-400">
                            The system continues working exactly as it is without shutting down your dashboard. However, you will not receive any new software version updates, security patches, or technical support tickets until you renew the annual license agreement.
                        </p>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="faq-item py-5">
                    <button type="button" class="faq-trigger flex w-full items-center justify-between text-left focus:outline-none">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Can we add modules later?</span>
                        <span class="faq-icon ml-6 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-550 dark:bg-slate-800"><i class="fa-solid fa-plus text-xs"></i></span>
                    </button>
                    <div class="faq-content hidden mt-3 max-h-0 overflow-hidden transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed dark:text-slate-400">
                            Yes. You can start with the Core Academic package and add modular extensions (like Finance or Teacher Evaluations) at any point. Adding a module incurs its one-time price plus its annual renewal increase.
                        </p>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="faq-item py-5">
                    <button type="button" class="faq-trigger flex w-full items-center justify-between text-left focus:outline-none">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Do you offer training?</span>
                        <span class="faq-icon ml-6 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-550 dark:bg-slate-800"><i class="fa-solid fa-plus text-xs"></i></span>
                    </button>
                    <div class="faq-content hidden mt-3 max-h-0 overflow-hidden transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed dark:text-slate-400">
                            Yes. Remote training sessions for your system administrators and academic registrar staff are included. On-site hands-on workshops are also available for an additional implementation fee based on locations.
                        </p>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="faq-item py-5">
                    <button type="button" class="faq-trigger flex w-full items-center justify-between text-left focus:outline-none">
                        <span class="text-sm font-bold text-slate-900 dark:text-white">What is the difference between self-hosted and managed hosting?</span>
                        <span class="faq-icon ml-6 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-550 dark:bg-slate-800"><i class="fa-solid fa-plus text-xs"></i></span>
                    </button>
                    <div class="faq-content hidden mt-3 max-h-0 overflow-hidden transition-all duration-300">
                        <p class="text-xs text-slate-500 leading-relaxed dark:text-slate-400">
                            Self-hosted means you provide your own server hardware, and our team installs it directly on your premise. Managed hosting means we host your school portal on our dedicated secure cloud server infrastructure and handle uptime monitoring, backup schedules, and configuration updates.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 9 — Contact / Quote request -->
    <section id="contact" class="py-24 bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-850 transition-colors duration-300">
        <div class="mx-auto max-w-xl px-6">
            <div class="text-center space-y-4 mb-10">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">GET STARTED</span>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white font-['Outfit']">
                    Request a Custom Quote
                </h2>
                <p class="text-slate-500 text-sm">
                    Submit the form below, and our integration team will reply with a detailed proposal within one business day.
                </p>
            </div>

            <!-- Card Wrap -->
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <!-- Inline Notification Alert -->
                <div id="contact-success" class="hidden rounded-xl bg-emerald-50 p-6 text-center text-emerald-850 dark:bg-emerald-950/40 dark:text-emerald-450 border border-emerald-500/20 space-y-4">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><i class="fa-solid fa-circle-check text-2xl animate-bounce"></i></div>
                    <h3 class="text-lg font-bold">Submitted Successfully</h3>
                    <p id="contact-success-msg" class="text-sm font-medium">Thank you. We will be in touch within one business day.</p>
                    <div id="download-pdf-section" class="hidden pt-4 border-t border-emerald-500/10">
                        <a id="download-pdf-link" href="#" target="_blank" class="inline-flex items-center justify-center space-x-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow hover:bg-emerald-500 transition duration-200 w-full sm:w-auto">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span>Download Proforma Invoice (PDF)</span>
                        </a>
                    </div>
                </div>


                <!-- Form -->
                <form id="quote-form" action="{{ route('quote-request') }}" method="POST" class="space-y-5">
                    @csrf
                    <!-- College name -->
                    <div>
                        <label for="college_name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 dark:text-slate-400">College Name</label>
                        <input type="text" id="college_name" name="college_name" required placeholder="e.g. Accra College of Education" class="w-full rounded-lg border-slate-300 dark:border-slate-800 dark:bg-slate-950 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <span class="error-msg text-[10px] text-red-500 hidden mt-1"></span>
                    </div>

                    <!-- Contact Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 dark:text-slate-400">Your Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. John Kow" class="w-full rounded-lg border-slate-300 dark:border-slate-800 dark:bg-slate-950 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <span class="error-msg text-[10px] text-red-500 hidden mt-1"></span>
                    </div>

                    <!-- Role Dropdown -->
                    <div>
                        <label for="role" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 dark:text-slate-400">Your Role</label>
                        <select id="role" name="role" required class="w-full rounded-lg border-slate-300 dark:border-slate-800 dark:bg-slate-950 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                            <option value="">Select your role...</option>
                            <option value="Principal">Principal</option>
                            <option value="Vice Principal">Vice Principal</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Finance Officer">Finance Officer</option>
                            <option value="IT Staff">IT Staff</option>
                            <option value="Other">Other</option>
                        </select>
                        <span class="error-msg text-[10px] text-red-500 hidden mt-1"></span>
                    </div>

                    <!-- Grid fields -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 dark:text-slate-400">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required placeholder="e.g. 0249100268" class="w-full rounded-lg border-slate-300 dark:border-slate-800 dark:bg-slate-950 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                            <span class="error-msg text-[10px] text-red-500 hidden mt-1"></span>
                        </div>
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 dark:text-slate-400">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="e.g. contact@college.edu.gh" class="w-full rounded-lg border-slate-300 dark:border-slate-800 dark:bg-slate-950 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                            <span class="error-msg text-[10px] text-red-500 hidden mt-1"></span>
                        </div>
                    </div>

                    <!-- Sync Hidden Fields from Calculator -->
                    <input type="hidden" id="form-student-band" name="student_band" value="1-500">
                    <input type="hidden" id="form-hosting-setup" name="hosting_setup" value="self_hosted">
                    <input type="hidden" id="form-config-setup" name="config_setup" value="0">
                    <input type="hidden" id="form-migration" name="migration" value="0">
                    <input type="hidden" id="form-admin-training" name="admin_training" value="0">
                    <input type="hidden" id="form-teacher-training" name="teacher_training" value="0">
                    <input type="hidden" id="form-onsite-training" name="onsite_training" value="0">
                    <input type="hidden" id="form-founding-client" name="founding_client" value="0">
                    <!-- Set to 1 only when user explicitly clicks Get a Quote from the calculator -->
                    <input type="hidden" id="form-send-receipt" name="send_client_receipt" value="0">
                    <div id="form-modules-container" class="hidden">
                        <!-- Module Checkboxes matching selection -->
                    </div>


                    <!-- Message -->
                    <div>
                        <label for="message" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 dark:text-slate-400">Message (Optional)</label>
                        <textarea id="message" name="message" rows="3" placeholder="Tell us about any specific customization requirements..." class="w-full rounded-lg border-slate-300 dark:border-slate-800 dark:bg-slate-950 focus:ring-emerald-500 focus:border-emerald-500 text-sm"></textarea>
                        <span class="error-msg text-[10px] text-red-500 hidden mt-1"></span>
                    </div>

                    <button type="submit" id="submit-form-btn" class="w-full rounded-xl bg-emerald-600 py-3.5 text-center text-sm font-bold text-white shadow hover:bg-emerald-500 transition duration-200 flex items-center justify-center space-x-2">
                        <span>Request a Quote</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Section 10 — Footer -->
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 py-16 transition-colors duration-300">
        <div class="mx-auto max-w-7xl px-6 grid gap-12 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Col 1 Logo/Details -->
            <div class="space-y-4">
                <a href="#" class="flex items-center space-x-2.5">
                    <img src="{{ asset('images/flowedu-logo.png') }}" alt="FlowEdu Logo" class="h-9 w-9 object-contain rounded-lg">
                    <span class="text-lg font-bold text-white tracking-tight">FlowEdu</span>
                </a>
                <p class="text-xs text-slate-500 leading-relaxed">
                    A premium school management suite crafted specifically to handle academic records, workflow routing, and fee administration for Ghanaian Colleges of Education.
                </p>
                <div class="space-y-1 text-xs font-medium text-slate-400">
                    <p class="flex items-center space-x-2">
                        <i class="fa-solid fa-phone text-emerald-500"></i>
                        <span>0249100268</span>
                    </p>
                    <p class="flex items-center space-x-2">
                        <i class="fa-solid fa-envelope text-emerald-500"></i>
                        <span>successinnovativehub@gmail.com</span>
                    </p>
                </div>
            </div>

            <!-- Col 2 Navigation -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-white uppercase tracking-wider">Navigation</h4>
                <ul class="space-y-2 text-xs font-medium text-slate-450">
                    <li><a href="#features" class="hover:text-white transition duration-150">Features Highlights</a></li>
                    <li><a href="#portals" class="hover:text-white transition duration-150">Institutional Portals</a></li>
                    <li><a href="#pricing" class="hover:text-white transition duration-150">Licensing Price Calculator</a></li>
                    <li><a href="#faq" class="hover:text-white transition duration-150">Frequently Asked Questions</a></li>
                </ul>
            </div>

            <!-- Col 3 Credentials -->
            <div class="space-y-4 lg:col-span-2">
                <h4 class="text-sm font-bold text-white uppercase tracking-wider">Built in Ghana for Ghanaian colleges</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Designed to fully comply with local grading structures (e.g. GPA brackets), academic schedules, administrative chains, and mobile money/payment workflows. Supported locally from Accra and Kumasi.
                </p>
                <div class="inline-flex items-center space-x-2.5 rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                    <i class="fa-solid fa-heart text-red-500 animate-pulse text-sm"></i>
                    <span class="text-[10px] font-bold text-slate-500">Made by Matme Inc.</span>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-6 border-t border-slate-800 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
            <p>© 2025 Matme Inc. All rights reserved.</p>
            <p class="flex items-center space-x-2"><i class="fa-solid fa-earth-africa"></i><span>Empowering education digital systems</span></p>
        </div>
    </footer>

    <!-- Embed Pricing Configuration for Client JS -->
    <script>
        window.pricingConfig = {!! json_encode([
            'bands'           => config('licence.core_pricing'),
            'modules'         => config('licence.modules'),
            'multipliers'     => config('licence.module_pricing.multipliers'),
            'bundle_discount' => config('licence.bundle_discount'),
            'founding_client_discount' => config('licence.founding_client_discount'),
        ]) !!};
    </script>

    <!-- Page Specific Script (Embedded for self-containment and performance) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Theme toggling sync with system
            const themeToggle = document.getElementById('theme-toggle');
            themeToggle?.addEventListener('click', function () {
                let dark = !document.documentElement.classList.contains('dark');
                document.documentElement.classList.toggle('dark', dark);
                localStorage.setItem('dark', JSON.stringify(dark));
            });

            // Mobile menu drawer
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const menuIconBars = document.getElementById('menu-icon-bars');
            const menuIconClose = document.getElementById('menu-icon-close');

            mobileMenuBtn?.addEventListener('click', function () {
                const open = mobileMenu.classList.contains('hidden');
                mobileMenu.classList.toggle('hidden', !open);
                menuIconBars.classList.toggle('hidden', open);
                menuIconClose.classList.toggle('hidden', !open);
            });

            // Auto collapse mobile drawer on link click
            document.querySelectorAll('.mobile-nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    menuIconBars.classList.remove('hidden');
                    menuIconClose.classList.add('hidden');
                });
            });

            // FAQ Accordion Toggle
            document.querySelectorAll('.faq-trigger').forEach(trigger => {
                trigger.addEventListener('click', function () {
                    const faqItem = this.closest('.faq-item');
                    const content = faqItem.querySelector('.faq-content');
                    const icon = this.querySelector('.faq-icon i');

                    const isOpen = !content.classList.contains('hidden');

                    // Close all first
                    document.querySelectorAll('.faq-content').forEach(c => {
                        c.classList.add('hidden');
                        c.style.maxHeight = '0px';
                    });
                    document.querySelectorAll('.faq-icon i').forEach(i => {
                        i.className = 'fa-solid fa-plus text-xs';
                    });

                    // Open current if it was closed
                    if (!isOpen) {
                        content.classList.remove('hidden');
                        content.style.maxHeight = '500px';
                        icon.className = 'fa-solid fa-minus text-xs';
                    }
                });
            });

            // Interactive Pricing State
            let selectedBand = '1-500';
            const selectedModules = new Set();

            function formatGHS(value) {
                return 'GHS ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function updatePricingView() {
                const config = window.pricingConfig;
                if (!config) return;

                const band = config.bands[selectedBand];
                const customQuoteView = document.getElementById('custom-quote-view');
                const calcGrid = document.getElementById('calculator-grid');

                // Check custom band (3500+)
                if (band && band.custom) {
                    customQuoteView.classList.remove('hidden');
                    calcGrid.classList.add('hidden');
                    document.getElementById('form-student-band').value = '3500+';
                    return;
                } else {
                    customQuoteView.classList.add('hidden');
                    calcGrid.classList.remove('hidden');
                }

                document.getElementById('form-student-band').value = selectedBand;

                // Multiplier & prices from band
                const multiplier = config.multipliers[selectedBand] || 1.0;
                let coreUpfront = band.core_upfront;
                let coreRenewal = band.core_renewal;

                // Check Founding Client Discount (15% off core)
                const applyFounding = document.getElementById('founding-client-chk').checked;
                let foundingDiscountUpfront = 0;
                let foundingDiscountRenew = 0;

                if (applyFounding) {
                    foundingDiscountUpfront = coreUpfront * config.founding_client_discount;
                    foundingDiscountRenew = coreRenewal * config.founding_client_discount;

                    coreUpfront -= foundingDiscountUpfront;
                    coreRenewal -= foundingDiscountRenew;

                    document.getElementById('founding-discount-row').classList.remove('hidden');
                    document.getElementById('summary-founding-discount').textContent = '-' + formatGHS(foundingDiscountUpfront);
                } else {
                    document.getElementById('founding-discount-row').classList.add('hidden');
                }

                // Update core prices labels
                document.getElementById('core-annual-price').textContent = formatGHS(band.core_upfront);
                document.getElementById('core-renew-price-label').textContent = 'Annual Renewal: ' + formatGHS(band.core_renewal) + ' / yr';

                // Update individual module cards price tags
                let modulesOnetimeSum = 0;
                let modulesRenewSum = 0;

                document.querySelectorAll('.module-card').forEach(card => {
                    const checkbox = card.querySelector('.module-checkbox');
                    const basePrice = parseFloat(card.querySelector('.module-onetime').dataset.base);
                    const baseRenew = parseFloat(card.querySelector('.module-renew').dataset.baseRenew);

                    const moduleOnetime = basePrice * multiplier;
                    const moduleRenew = baseRenew * multiplier;

                    card.querySelector('.module-onetime').textContent = formatGHS(moduleOnetime);
                    card.querySelector('.module-renew').textContent = formatGHS(moduleRenew) + ' / yr';

                    if (checkbox.checked) {
                        modulesOnetimeSum += moduleOnetime;
                        modulesRenewSum += moduleRenew;
                    }
                });

                // Apply bundle discount (12% off when 4 or more modules selected)
                let discountAmtOnetime = 0;
                let discountAmtRenew = 0;
                const numModulesSelected = selectedModules.size;

                if (numModulesSelected >= 4) {
                    discountAmtOnetime = modulesOnetimeSum * config.bundle_discount;
                    discountAmtRenew = modulesRenewSum * config.bundle_discount;

                    document.getElementById('bundle-discount-row').classList.remove('hidden');
                    document.getElementById('bundle-alert-banner').classList.remove('hidden');
                    document.getElementById('summary-discount').textContent = '-' + formatGHS(discountAmtOnetime);
                } else {
                    document.getElementById('bundle-discount-row').classList.add('hidden');
                    document.getElementById('bundle-alert-banner').classList.add('hidden');
                }

                const modulesOnetimeFinal = modulesOnetimeSum - discountAmtOnetime;
                const modulesRenewFinal = modulesRenewSum - discountAmtRenew;

                // Implementation Setup Cost Calculations
                let hostingSetupFee = 0;
                const hostingRadio = document.querySelector('input[name="hosting_setup"]:checked').value;
                if (hostingRadio === 'self_hosted') {
                    hostingSetupFee = 1200.00;
                } else if (hostingRadio === 'managed') {
                    hostingSetupFee = 1600.00;
                }

                // Config and Migration checkboxes
                let configurationFee = 0;
                if (document.getElementById('config-setup-chk').checked) {
                    configurationFee += 800.00;
                }
                if (document.getElementById('migration-chk').checked) {
                    configurationFee += 2000.00;
                }

                // Training fees
                const adminTrainQty = parseInt(document.getElementById('admin-train-qty').value) || 0;
                const teacherTrainQty = parseInt(document.getElementById('teacher-train-qty').value) || 0;
                const onsiteTrainQty = parseInt(document.getElementById('onsite-train-qty').value) || 0;

                const trainingFeeSum = (adminTrainQty * 600.00) + (teacherTrainQty * 500.00) + (onsiteTrainQty * 1500.00);

                // Update Projected Summary details
                document.getElementById('selected-summary-count').textContent = 'Core + ' + numModulesSelected + ' module(s)';
                document.getElementById('summary-core-annual').textContent = formatGHS(band.core_upfront);
                document.getElementById('summary-core-renew').textContent = formatGHS(band.core_renewal);
                document.getElementById('summary-modules-onetime').textContent = formatGHS(modulesOnetimeSum);
                document.getElementById('summary-modules-renew').textContent = formatGHS(modulesRenewSum);
                
                document.getElementById('summary-impl-setup').textContent = formatGHS(hostingSetupFee);
                document.getElementById('summary-impl-training').textContent = formatGHS(configurationFee + trainingFeeSum);

                // Upfront Total = Core Upfront + Modules Upfront + Hosting + Configuration + Training
                const upfrontTotal = coreUpfront + modulesOnetimeFinal + hostingSetupFee + configurationFee + trainingFeeSum;
                // Renewal Total = Core Renewal + Modules Renewal
                const renewTotal = coreRenewal + modulesRenewFinal;

                document.getElementById('total-upfront-price').textContent = formatGHS(upfrontTotal);
                document.getElementById('total-renew-price').textContent = formatGHS(renewTotal) + ' / year';

                // Sync modules to hidden contact form fields
                const formModulesContainer = document.getElementById('form-modules-container');
                formModulesContainer.innerHTML = '';
                selectedModules.forEach(mod => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'modules[]';
                    input.value = mod;
                    formModulesContainer.appendChild(input);
                });

                // Sync other fields to form
                document.getElementById('form-hosting-setup').value = hostingRadio;
                document.getElementById('form-config-setup').value = document.getElementById('config-setup-chk').checked ? 1 : 0;
                document.getElementById('form-migration').value = document.getElementById('migration-chk').checked ? 1 : 0;
                document.getElementById('form-admin-training').value = adminTrainQty;
                document.getElementById('form-teacher-training').value = teacherTrainQty;
                document.getElementById('form-onsite-training').value = onsiteTrainQty;
                document.getElementById('form-founding-client').value = applyFounding ? 1 : 0;
            }

            // Segmented Band Selectors Event
            document.querySelectorAll('.band-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.band-btn').forEach(b => {
                        b.className = 'band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200 text-slate-500 hover:text-slate-900 dark:hover:text-white';
                    });
                    this.className = 'band-btn rounded-lg px-4 py-2 text-xs font-bold transition duration-200 bg-white text-emerald-600 shadow dark:bg-slate-800 dark:text-emerald-400';
                    selectedBand = this.dataset.band;
                    
                    const selectMobile = document.getElementById('student-band-select-mobile');
                    if (selectMobile) selectMobile.value = selectedBand;

                    updatePricingView();
                });
            });

            const selectMobile = document.getElementById('student-band-select-mobile');
            selectMobile?.addEventListener('change', function () {
                selectedBand = this.value;
                document.querySelectorAll('.band-btn').forEach(btn => {
                    if (btn.dataset.band === selectedBand) {
                        btn.click();
                    }
                });
                updatePricingView();
            });

            // Module check card trigger
            document.querySelectorAll('.module-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const card = this.closest('.module-card');
                    if (this.checked) {
                        selectedModules.add(this.value);
                        card.className = 'module-card relative flex flex-col justify-between rounded-xl border border-emerald-500/50 bg-emerald-50/15 p-5 cursor-pointer shadow-sm select-none dark:border-emerald-400/30 dark:bg-emerald-950/10 transition-colors duration-200';
                    } else {
                        selectedModules.delete(this.value);
                        card.className = 'module-card relative flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 cursor-pointer shadow-sm select-none hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200';
                    }
                    updatePricingView();
                });
            });

            // Setup listeners for setup/training fields
            document.querySelectorAll('input[name="hosting_setup"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    // Update borders
                    document.querySelectorAll('.hosting-option').forEach(el => {
                        el.classList.remove('border-emerald-500/35');
                    });
                    this.closest('.hosting-option').classList.add('border-emerald-500/35');
                    updatePricingView();
                });
            });

            document.getElementById('config-setup-chk').addEventListener('change', function () {
                this.closest('.impl-addon').classList.toggle('border-emerald-500/35', this.checked);
                this.closest('.impl-addon').classList.toggle('bg-emerald-50/10', this.checked);
                updatePricingView();
            });

            document.getElementById('migration-chk').addEventListener('change', function () {
                this.closest('.impl-addon').classList.toggle('border-emerald-500/35', this.checked);
                this.closest('.impl-addon').classList.toggle('bg-emerald-50/10', this.checked);
                updatePricingView();
            });

            document.getElementById('admin-train-qty').addEventListener('change', updatePricingView);
            document.getElementById('teacher-train-qty').addEventListener('change', updatePricingView);
            document.getElementById('onsite-train-qty').addEventListener('change', updatePricingView);
            document.getElementById('founding-client-chk').addEventListener('change', updatePricingView);

            // Prefill Button scrolls to Contact Form and marks the receipt flag
            document.getElementById('quote-prefill-btn')?.addEventListener('click', function () {
                document.getElementById('form-send-receipt').value = '1';
                document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
            });

            // Navbar / hero "Get a quote" links also mark the receipt flag
            document.querySelectorAll('a[href="#contact"]').forEach(function (link) {
                link.addEventListener('click', function () {
                    document.getElementById('form-send-receipt').value = '1';
                });
            });

            // Initial Trigger
            const firstBandBtn = document.querySelector('[data-band="1-500"]');
            if (firstBandBtn) firstBandBtn.click();

            // Handle Contact Form AJAX Submission
            const form = document.getElementById('quote-form');
            form?.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = document.getElementById('submit-form-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <i class="fa-solid fa-spinner animate-spin mr-2"></i>
                    <span>Submitting...</span>
                `;

                // Clear errors
                document.querySelectorAll('.error-msg').forEach(el => {
                    el.classList.add('hidden');
                    el.textContent = '';
                });

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (status === 200 && body.success) {
                        form.classList.add('hidden');
                        document.getElementById('contact-success').classList.remove('hidden');

                        // Update the success message from server
                        const successMsg = document.getElementById('contact-success-msg');
                        if (successMsg && body.message) {
                            successMsg.textContent = body.message;
                        }

                        // Only show the PDF download button if the server returned a download URL
                        if (body.download_url) {
                            const pdfLink = document.getElementById('download-pdf-link');
                            const pdfSection = document.getElementById('download-pdf-section');
                            if (pdfLink) pdfLink.href = body.download_url;
                            if (pdfSection) pdfSection.classList.remove('hidden');
                        }
                    } else if (status === 422) {
                        // Display Validation errors inline
                        Object.keys(body.errors).forEach(field => {
                            const input = document.getElementById(field) || document.getElementsByName(field)[0];
                            if (input) {
                                const parent = input.closest('div');
                                const errorSpan = parent.querySelector('.error-msg');
                                if (errorSpan) {
                                    errorSpan.textContent = body.errors[field][0];
                                    errorSpan.classList.remove('hidden');
                                }
                            }
                        });
                    } else {
                        alert('Something went wrong. Please try again.');
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    alert('An error occurred. Please check your network connection.');
                });
            });
        });
    </script>
</body>
</html>
