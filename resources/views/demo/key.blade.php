<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/flowedu-favicon.png') }}">

    <title>FlowEdu Demo — Enter access key</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
    <div class="min-h-screen flex flex-col">
        <header class="w-full border-b border-slate-200 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <span class="flex items-center space-x-2.5">
                    <img src="{{ asset('images/flowedu-logo.png') }}" alt="FlowEdu Logo" class="h-10 w-10 object-contain rounded-lg shadow-md">
                    <span>
                        <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Flow<span class="text-emerald-600 dark:text-emerald-400">Edu</span></span>
                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-medium tracking-widest uppercase">by Matme Inc</span>
                    </span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Demo
                </span>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-6 py-16">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <h1 class="font-['Outfit'] text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Enter your demo key
                </h1>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    This demo environment is key-gated. Enter the access key to continue.
                </p>

                @if(! empty($error))
                    <p class="mt-4 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 dark:bg-red-950/40 dark:text-red-300" role="alert">
                        {{ $error }}
                    </p>
                @endif

                <form method="POST" action="{{ route('demo.key.store') }}" class="mt-6 space-y-4" x-data="{ code: '', get valid() { return /^demo1\.[A-Za-z0-9\-_=]+\.[A-Za-z0-9\-_=]+$/.test(this.code.trim()); } }">
                    @csrf
                    <div>
                        <label for="demo-key" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Access key</label>
                        <input
                            id="demo-key"
                            name="code"
                            type="text"
                            autocomplete="off"
                            autofocus
                            required
                            minlength="20"
                            maxlength="256"
                            x-model="code"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 font-mono text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            placeholder="e.g. demo1.eyJoIjoiZGVtby5leGFtcGxlLmNvbSJ9..."
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Paste the full demo key (starts with <span class="font-mono">demo1.</span>).</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="! valid"
                        :class="valid ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                        class="flex w-full items-center justify-center rounded-lg px-6 py-3 text-base font-bold text-white shadow-lg shadow-emerald-600/20 transition"
                    >
                        Continue to demo
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
