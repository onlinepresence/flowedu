/**
 * Global Livewire UX helpers: top progress bar + offline banner.
 * Pure JS/Alpine-free so it works outside Livewire component scope
 * (layout chrome, header slots) and on slow networks.
 */
(function () {
    function progressEls() {
        return {
            wrap: document.getElementById('college-top-progress'),
            bar: document.getElementById('college-top-progress-bar'),
            banner: document.getElementById('college-offline-banner'),
        };
    }

    let pending = 0;
    let showTimer = null;

    function showProgress() {
        const { wrap, bar } = progressEls();
        if (!wrap || !bar) return;
        pending += 1;
        // Avoid flicker on fast (<200ms) requests: only show if still pending.
        if (showTimer !== null) return;
        showTimer = window.setTimeout(() => {
            if (pending > 0) {
                wrap.classList.remove('hidden');
                bar.classList.add('animate-pulse');
            }
            showTimer = null;
        }, 200);
    }

    function hideProgress() {
        const { wrap, bar } = progressEls();
        pending = Math.max(0, pending - 1);
        if (pending > 0) return;
        if (showTimer !== null) {
            window.clearTimeout(showTimer);
            showTimer = null;
        }
        if (!wrap || !bar) return;
        bar.classList.remove('animate-pulse');
        wrap.classList.add('hidden');
    }

    function setOffline(offline) {
        const { banner } = progressEls();
        if (!banner) return;
        banner.classList.toggle('hidden', !offline);
        banner.classList.toggle('flex', offline);
    }

    window.addEventListener('online', () => setOffline(false));
    window.addEventListener('offline', () => setOffline(true));
    setOffline(typeof navigator !== 'undefined' && navigator.onLine === false);

    document.addEventListener('livewire:init', () => {
        try {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('commit', ({ succeed, fail }) => {
                    showProgress();
                    succeed(() => window.queueMicrotask(hideProgress));
                    fail(() => window.queueMicrotask(hideProgress));
                });
            }
        } catch (e) {
            // Never break the app for a progress indicator.
        }
    });
})();
