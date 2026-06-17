const TOAST_TTL_MS = 6500;

const variantClasses = {
    success:
        'border-green-200 bg-green-50 text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100',
    danger: 'border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100',
    warning:
        'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100',
    info: 'border-gray-200 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100',
};

function toastContainer() {
    let el = document.getElementById('college-toast-container');
    if (!el) {
        el = document.createElement('div');
        el.id = 'college-toast-container';
        el.className =
            'pointer-events-none fixed bottom-4 right-4 z-[200] flex w-full max-w-sm flex-col gap-2 px-4 sm:bottom-6 sm:right-6 sm:px-0';
        el.setAttribute('aria-live', 'polite');
        document.body.appendChild(el);
    }

    return el;
}

function dismissButton(onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className =
        'shrink-0 rounded p-1 opacity-70 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50';
    btn.setAttribute('aria-label', 'Dismiss');
    btn.innerHTML =
        '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
    btn.addEventListener('click', onClick);

    return btn;
}

function showCollegeToast(message, variant = 'success') {
    if (!message) {
        return;
    }

    const container = toastContainer();
    const wrap = document.createElement('div');
    wrap.className = `pointer-events-auto flex items-start gap-3 rounded-lg border p-4 shadow-lg dark:shadow-black/40 ${variantClasses[variant] ?? variantClasses.success}`;

    const text = document.createElement('p');
    text.className = 'min-w-0 flex-1 text-sm leading-snug';
    text.textContent = message;

    const remove = () => wrap.remove();

    wrap.appendChild(text);
    wrap.appendChild(dismissButton(remove));
    container.appendChild(wrap);

    window.setTimeout(remove, TOAST_TTL_MS);
}

function consumeInitialScript() {
    const el = document.getElementById('college-toast-initial');
    if (!el || el.tagName !== 'SCRIPT') {
        return;
    }

    let items = [];
    try {
        items = JSON.parse(el.textContent || '[]');
    } catch {
        items = [];
    }
    el.remove();

    if (!Array.isArray(items)) {
        return;
    }

    items.forEach((row) => {
        if (row && row.message) {
            showCollegeToast(String(row.message), row.variant || 'success');
        }
    });
}

function parseToastArgs(detailOrArgs) {
    let message = '';
    let variant = 'success';

    if (!detailOrArgs) {
        return { message, variant };
    }

    if (Array.isArray(detailOrArgs)) {
        // Positional arguments from array
        message = detailOrArgs[0] ?? '';
        variant = detailOrArgs[1] ?? 'success';
    } else if (typeof detailOrArgs === 'object') {
        // Named arguments from object
        message = detailOrArgs.message ?? detailOrArgs[0] ?? '';
        variant = detailOrArgs.variant ?? detailOrArgs[1] ?? 'success';
    } else if (typeof detailOrArgs === 'string') {
        // Just a string
        message = detailOrArgs;
    }

    return {
        message: String(message),
        variant: String(variant)
    };
}

function onCollegeToastEvent(e) {
    const parsed = parseToastArgs(e.detail);
    if (parsed.message) {
        showCollegeToast(parsed.message, parsed.variant);
    }
}

let listenersAttached = false;

function attachGlobalListeners() {
    if (listenersAttached) {
        return;
    }
    listenersAttached = true;
    window.addEventListener('college-toast', onCollegeToastEvent);
}

document.addEventListener('DOMContentLoaded', () => {
    attachGlobalListeners();
    consumeInitialScript();
});

document.addEventListener('livewire:init', () => {
    attachGlobalListeners();
    Livewire.on('college-toast', (eventData) => {
        const parsed = parseToastArgs(eventData);
        if (parsed.message) {
            showCollegeToast(parsed.message, parsed.variant);
        }
    });
});

document.addEventListener('livewire:navigated', () => {
    consumeInitialScript();
});
