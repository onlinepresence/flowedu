# College shell vs legacy Windmill layout

This documents how the Laravel Livewire **college shell** (`resources/views/components/layouts/college-shell.blade.php`) aligns with the legacy PHP layout (`layouts/auth.php`, `layouts/parts/auth_nav.php`) and where it intentionally differs.

## Frame

- **Legacy:** `flex h-screen` on the outer wrapper, `overflow-hidden` on that wrapper when the mobile menu is open.
- **College shell:** Same pattern: `flex h-screen overflow-hidden` with Alpine `:class="{ 'overflow-hidden': sidebarOpen }"`.

## Header

- **Legacy:** Full-width row with purple-tinted text, hamburger (mobile), centered search (disabled), theme toggle, notifications dropdown, profile dropdown.
- **College shell:** Matches structure and styling intent (purple accents, `focus:ring-2 focus:ring-purple-500/40` instead of legacy `focus:shadow-outline-purple`). Search field is visible and disabled like legacy.

## Sidebar (desktop)

- **Legacy:** `hidden lg:block` (conceptually), `w-64`, white / `dark:bg-gray-800`, brand link `ml-6`, nav `mt-6`, full-width purple **Log out** in `px-6 my-6`.
- **College shell:** Sidebar is the first flex child on large screens (`lg:static`, `lg:h-full`, `w-64`), same brand/nav spacing, **Log out** via `livewire:layout.logout-button` with `variant="sidebar"` (`bg-purple-600` / `hover:bg-purple-700`, rounded).

## Mobile sidebar & backdrop

- **Legacy:** Backdrop `fixed inset-0 z-10`, mobile drawer `fixed … z-20`, **`mt-16`** so the drawer starts **below** the header (header remains visible and usable).
- **College shell:** Same choice: drawer uses `top-16` and `h-[calc(100vh-4rem)]`. Backdrop is `fixed inset-0 z-40` (shell only); the header uses `z-50` so it stays **above** the backdrop and remains clickable (legacy relied on DOM stacking with the same z-index as the backdrop).

## Single sidebar instance

- **Legacy:** Duplicates nav markup for desktop aside vs mobile aside.
- **College shell:** One `<aside>` and one Livewire nav tree to avoid duplicate Livewire roots; responsive positioning handles desktop vs mobile.

## Icons

- **Legacy:** Font Awesome (`fas fa-*`) in the nav.
- **College shell:** Font Awesome 6 classes (`fa-solid fa-*`) in `nav-icon.blade.php`, driven by the same logical icon keys as before (mapped from heroicon-style names in PHP).

## Dark mode

- **Tailwind:** `darkMode: 'class'` in `tailwind.config.js` so `dark:*` utilities follow the `dark` class on `<html>`.
- **No flash:** An inline synchronous script in the layout `<head>` (college shell and guest) applies `document.documentElement.classList` from `localStorage` key **`dark`** (JSON boolean) or falls back to `prefers-color-scheme`, matching legacy `assets/js/init-alpine.js`.
- **After load:** `resources/js/college-theme.js` re-applies the same rules on `livewire:navigated` so navigations cannot desync the theme. The authenticated header uses Alpine `toggleTheme()` to flip `dark`, update `localStorage`, and toggle the class on `<html>`.
- **Guest layout:** Uses a small vanilla `click` handler (not Alpine) so the toggle still works on pages that do not load Livewire, while reusing the same `localStorage` key and `<html class="dark">` behaviour.

## Preserved behaviour

- `wire:navigate` on sidebar links (nav tree).
- Impersonation banner and routes unchanged.
- Logout remains a Livewire action (`LogoutButton`) for CSRF/session handling; profile menu includes an additional **Log out** row (`variant="menu"`).
