/**
 * Syncs Tailwind `dark` class with the same localStorage key as legacy Alpine (`dark`, JSON boolean).
 * Runs on livewire navigations so theme stays consistent if the document is replaced.
 */
function applyCollegeThemeFromStorage() {
    try {
        const raw = window.localStorage.getItem('dark');
        const dark =
            raw !== null
                ? JSON.parse(raw)
                : window.matchMedia?.('(prefers-color-scheme: dark)').matches === true;
        document.documentElement.classList.toggle('dark', Boolean(dark));
    } catch {
        /* ignore */
    }
}

applyCollegeThemeFromStorage();

document.addEventListener('livewire:navigated', () => {
    applyCollegeThemeFromStorage();
});
