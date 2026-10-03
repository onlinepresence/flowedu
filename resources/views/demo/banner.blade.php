{{-- Demo trial banner: owner/admin only, and only when the trial carries an
     expiry date. Never-expiring keys and not-yet-verified installs show
     nothing — the banner is a countdown reminder, not a mode pill (the
     sidebar/header already carry the generic Demo Mode marker). Status is
     sourced offline from the cached (or stored) signed document — never a
     network call. --}}
@php
    $demoBannerStatus = null;
    $demoBannerShow = false;
    try {
        $demoBannerUser = auth()->user();
        if ((bool) config('college.demo_mode', false)
            && $demoBannerUser !== null
            && ($demoBannerUser->type ?? null) === 'admin') {
            $demoBannerStatus = app(\App\Services\DemoKeyVerifier::class)->demoStatus();
            $demoBannerShow = is_array($demoBannerStatus)
                && ! ($demoBannerStatus['never'] ?? true)
                && trim((string) ($demoBannerStatus['expires_at'] ?? '')) !== '';
        }
    } catch (\Throwable $e) {
        $demoBannerShow = false;
        $demoBannerStatus = null;
    }
    $demoBannerHosts = [];
    if (is_array($demoBannerStatus)) {
        $rawHost = $demoBannerStatus['host'] ?? null;
        $demoBannerHosts = is_array($rawHost)
            ? array_values(array_filter(array_map('strval', $rawHost)))
            : (is_string($rawHost) && trim($rawHost) !== '' ? [trim($rawHost)] : []);
    }
@endphp
@if($demoBannerShow)
    <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-xs font-semibold text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100 sm:px-6" role="status">
        <span class="inline-flex items-center gap-1.5">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
            {{ __('Demo trial — full access') }}
        </span>
        <span aria-hidden="true">·</span>
        <span>
            {{ __('Valid until: :date', ['date' => (string) ($demoBannerStatus['expires_at'] ?? '')]) }}
        </span>
        @if($demoBannerHosts !== [])
            <span aria-hidden="true">·</span>
            <span>{{ __('Locked to :hosts', ['hosts' => implode(', ', $demoBannerHosts)]) }}</span>
        @endif
    </div>
@endif
