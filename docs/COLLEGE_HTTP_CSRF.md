# CSRF, web POST routes, and validated input

## Web middleware

All routes registered with the `web` middleware group (including `routes/web.php`, `routes/auth.php`, and included `admin.php`, `student.php`, `teacher.php`) use Laravel’s `VerifyCsrfToken` middleware. **Livewire** component requests include the CSRF token automatically.

## Axios / `fetch`

[`resources/js/bootstrap.js`](../resources/js/bootstrap.js) sets `X-CSRF-TOKEN` from the `<meta name="csrf-token">` tag present on [`college-shell`](../resources/views/components/layouts/college-shell.blade.php) and Breeze [`guest`](../resources/views/layouts/guest.blade.php) layouts. Any custom page that uses Axios without those layouts must include the same meta tag (or equivalent header).

## JSON POST stub

`POST /__college/ajax/ping` ([`routes/web.php`](../routes/web.php)) is authenticated and **requires** a valid CSRF token when called from JavaScript (same as any `web` POST).

## Mass assignment

Prefer Eloquent `$fillable` / `$guarded`, **Form Request** `validated()` data, or Livewire `#[Validate]` / `$this->validate()` — never persist raw `request()->all()` for models.

## Audit snapshot (generated)

| Area | POST / mutating routes | Notes |
|------|------------------------|--------|
| Auth | Livewire Volt pages | CSRF via Livewire |
| Admin / student / teacher | Mostly `Route::get` + Livewire full-page components | No custom JSON submit gateways yet |
| Admin grading | `EnterGradesPage` (`admin.grading.enter`) — `saveResult` | Mutations go through Livewire’s POST (`/livewire/update`); input validated with `App\Http\Requests\Admin\UpdateResultScoreRequest::draftScoreRules()`; persistence in `App\Actions\Grading\UpdateResultScoreAction` |
| Student admission | `StudentSetupPersonalPage`, `StudentSetupGuardianPage`, `StudentSetupStatusPage`, `StudentSetupDeletePage`, `StudentProfilePage` | Mutations via Livewire + Form Requests under `App\Http\Requests\Student\*`; file uploads to `college_uploads` |
| `web.php` | `__college/ajax/ping` | Uses `web` + CSRF |

Re-run this audit when adding new `Route::post` endpoints outside Livewire.
