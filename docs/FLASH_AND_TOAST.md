# Flash messages and two-request parity

Legacy PHP used `send_to_next_request()` so success and error messages could survive an extra full page cycle before `flush_session()` cleared them (see root `includes/functions.php`).

## Laravel pattern

- **Normal one-request flash:** `session()->flash('status', __('Saved.'));` — shown on the **next** request only.
- **Two-request flash (legacy parity):** use [`App\Support\CollegeFlash::forNextRequestToo()`](../app/Support/CollegeFlash.php):

```php
use App\Support\CollegeFlash;

CollegeFlash::forNextRequestToo('status', __('School details have been saved.'));
```

[`ExtendUserFlash`](../app/Http/Middleware/ExtendUserFlash.php) is appended to the `web` middleware stack in [`bootstrap/app.php`](../bootstrap/app.php). When `college.flash_extend` is set, it calls `session()->reflash()` so the current flash payload is kept for one additional request.

## When to use which

| Situation | Use |
|-----------|-----|
| Standard redirect / Livewire navigation where one flash is enough | `session()->flash(...)` |
| Flows that mirror legacy double round-trip or noisy redirects | `CollegeFlash::forNextRequestToo(...)` |

## Toasts (UI)

The college shell ([`resources/views/components/layouts/college-shell.blade.php`](../resources/views/components/layouts/college-shell.blade.php)) includes [`x-college.toast-stack`](../resources/views/components/college/toast-stack.blade.php), which:

1. **Hydrates from session** — `status` (success) and `backup_error` (danger), except `status === 'verification-link-sent'` (reserved for the verify-email page).
2. **Listens for Livewire** — components can use [`App\Livewire\Concerns\DispatchesCollegeToasts`](../app/Livewire/Concerns/DispatchesCollegeToasts.php) and `$this->collegeToast(__('Message'), 'success'|'danger'|...)`.
3. **Runs in vanilla JS** — [`resources/js/college-toasts.js`](../resources/js/college-toasts.js) (loaded from [`resources/js/app.js`](../resources/js/app.js)); dispatches stack in the bottom-right with auto-dismiss.

For **same-request** feedback after a Livewire action (no redirect), prefer `collegeToast`. For **redirect / `wire:navigate`** flows, keep `session()->flash` or `CollegeFlash::forNextRequestToo` so the next paint picks up the message from session into a toast.
