# Operations: maintenance, scheduler, queues

## Package licence and navigation (manual check matrix)

[`SchoolLicenceService`](../app/Services/SchoolLicenceService.php) enforces tier keys from [`config/licence.php`](../config/licence.php). Sidebar filtering uses [`NavigationLicenceService`](../app/Services/NavigationLicenceService.php), aligned with legacy `includes/licence.php` URL rules.

| Feature key | Example paths (prefix or match) | Expected when tier below minimum |
|---------------|-----------------------------------|----------------------------------|
| `finance` | `/admin/finance/*`, `/student/fees`, `/student/payment-history`, `/student/allowance` | Finance admin group and student “Fees & Payments” hidden from nav |
| `reports` | `/admin/reports/*` | Reports admin group hidden |
| `staff` | `/admin/staff/*` except paths containing `/admin/staff/evaluation` | Administration / most staff nav hidden; evaluation items use `evaluations` |
| `evaluations` | `/admin/staff/evaluation*`, `/student/evaluation*` | Evaluation links hidden |
| `student_professional` | `/admin/students/promotion|graduation|medical|discipline`, `/student/clearance`, `/student/medical`, `/student/discipline` | Those items hidden |
| `system_admin` | `/admin/settings/roles`, `users`, `image-validation`, `backup`, `/env-generator` | Those settings links hidden (`/tools/*` is middleware-only; not in legacy URL map) |

With `LICENCE_ENFORCE=false` (see `config/licence.php` `enforce`), all `can()` checks pass and nothing is hidden by tier.

## Enrollment (ControlDesk codes, offline, file import)

The setup wizard licence step (after school, before programs) is a mandatory
three-exit gate — later setup steps redirect back until one is chosen:

- **(a) Redeem code now** — `POST {CONTROL_PLANE_URL}/api/v1/enroll`
  `{code, app_version}`. ControlDesk answers the authoritative FLAT shape:
  `{deployment_uuid, heartbeat_token, licence{tier, modules[], caps{},
  valid_until}}` on 200; `{message, error}` on 422 with `unknown_code |
  code_voided | code_expired | attempts_exceeded | deployment_mismatch |
  deployment_revoked | unbound_code` (a 200 with no token but a licence
  means consumed-replay). The client normalizes this to the internal
  snapshot at the boundary, then seeds `school_licences` FROM it
  (`external_ref` = deployment UUID, `valid_until` → `licence_end` +
  `support_until`, `starts_at` defaulting to today) and writes
  `DEPLOYMENT_UUID` + `CONTROL_PLANE_TOKEN` to `.env` (verified by re-read;
  if the write fails the exact lines are shown for manual paste).
- **(b) Continue offline** — provisional core-only row (`provisional = true`),
  setup completes. An optional code is parked (`controlplane.pending_code`
  setting); the daily `licence-retry-pending` job redeems it silently the
  next time the network answers, and the first success overwrites the
  provisional row with central truth.
- **(c) Import licence file** — paste (or pick) a signed JSON blob
  `{"payload": {...}, "signature": "<base64 ed25519>"}`. Verified against the
  baked-in public key (`CONTROL_PLANE_PUBLIC_KEY`) plus date validity
  (`expires_at` must be today or later); seeds the row exactly like (a).

Expired/consumed/unrecognized codes get human messages ("ask ops for a fresh
code") — the offline exit is always present, so there are no dead ends.
**Reinstall = new code**: each install consumes its own code; wiping and
reinstalling requires a fresh one from ops.

Linked installs (`DEPLOYMENT_UUID` set and matching the row) render the
licence step **read-only** ("Managed by ControlDesk"); provisional installs
keep local editing badged **PROVISIONAL**; installs with neither behave
exactly as before. Heartbeats go to `POST {CONTROL_PLANE_URL}/api/v1/heartbeats`
(Bearer token) with `{deployment_uuid, product: "flowedu", app_version,
counts{students, teachers, users}, modules_in_use[]}`. Test without the
wizard: `php artisan controlplane:ping --redeem CODE`.

## Admin impersonation (replaces legacy SYSTEM_PASSWORD)

**Who may impersonate:** admin users whose role is `owner` or `system_admin` (see [`AdminSystemSeeder`](../database/seeders/AdminSystemSeeder.php)).

**Flow:** Admin opens **Impersonate user** ([`admin.impersonation.index`](../routes/admin.php)), picks an account. The app logs in as that user, sets session flags, and shows an **Exit impersonation** banner (see [`college-shell`](../resources/views/components/layouts/college-shell.blade.php)). Stopping restores the original admin and sets `ended_at` on the audit row.

**Audit:** Rows in `admin_impersonation_logs` (`impersonator_user_id`, `impersonated_user_id`, `started_at`, `ended_at`, `ip_address`, `user_agent`).

**Policy:** [`UserPolicy::impersonate`](../app/Policies/UserPolicy.php) — cannot impersonate self, inactive users, or anyone who may start impersonation (prevents impersonating peer privileged admins).

**Stop route:** `POST` [`impersonation.stop`](../routes/web.php) — requires an active impersonation session (any user type while banner is shown).

## Two kinds of “down”

| Mechanism | Config / command | Behaviour |
|-----------|------------------|-----------|
| **Legacy parity** | `SERVER_DOWN=true` in `.env` → [`config/college.php`](../config/college.php) | [`EnsureServerNotDown`](../app/Http/Middleware/EnsureServerNotDown.php) redirects browsers to named route `shutdown` (except `/shutdown`, `/up`). |
| **Laravel maintenance** | `php artisan down` / `php artisan up` | Framework maintenance page; use for deploys and framework-level outages. |

Use **Laravel `down`** for deployments; use **`SERVER_DOWN`** when mirroring legacy “college closed” messaging to `/shutdown`.

## Scheduler

Defined in [`routes/console.php`](../routes/console.php). Production crontab should include:

```text
* * * * * cd /path/to/new-college && php artisan schedule:run >> /dev/null 2>&1
```

Tasks: evaluation maintenance (hourly), semester status (hourly), auto-promotion (monthly on the 15th at 03:00), licence redemption retry (daily at 04:00; silent no-op without a parked code). Manual run: `php artisan college:maintenance`. Manual heartbeat: `php artisan controlplane:ping [--redeem CODE]`.

## Demo (single-connection)

`APP_DEMO=true` with `DB_*` pointed at the demo MySQL database IS the whole mechanism.
No second DB, no runtime connection swapping. Demo vs production is one flag
(`config('college.demo_mode')` wired to `APP_DEMO`) over identical code.

When `APP_DEMO=true`: demo banner on all pages, demo credentials hint on login,
mail forced to `log` driver, public registration closed (seeded users only).
Licence enforcement STAYS ON — the seeded `school_licences` row governs features.

Key gate: `DEMO_KEY` env bypasses the key-entry screen entirely (the hosted instance
lives here). Without it, visitors see `GET /demo/key` (403-style, FlowEdu landing look,
Alpine form, no Livewire) and must enter a key once per session
(`session('demo_key_accepted')`). Validation lives in
[`DemoKeyVerifier`](../app/Services/DemoKeyVerifier.php) — currently any 8–64 char
`[A-Za-z0-9-_]` code is accepted and logged; the seam is marked with a TODO for the
future ControlDesk verify call.

### Provisioning the demo DB + user (DDL scoped to it)

```sql
-- Final database/user names to be confirmed by owner.
CREATE DATABASE IF NOT EXISTS `flowedu_demo`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'flowedu_demo'@'127.0.0.1' IDENTIFIED BY 'change-me';
GRANT ALL PRIVILEGES ON `flowedu_demo`.* TO 'flowedu_demo'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Point the demo host `.env` at it (`DB_DATABASE=flowedu_demo`, `DB_USERNAME=flowedu_demo`),
set `APP_DEMO=true` and `DEMO_KEY=<hosted-key>`, then `php artisan migrate --force`
plus `php artisan db:seed --class="Database\Seeders\DemoDataSeeder" --force`.

### Monthly refresh

`php artisan demo:refresh` runs `migrate:fresh` on the DEFAULT connection plus
`DemoDataSeeder`. Its only restriction is `APP_DEMO=true` in env (no database-name
check). [`routes/console.php`](../routes/console.php) registers it
(`demo-refresh-monthly`, monthly on the 1st at 03:00) ONLY when `APP_DEMO` is true —
never unconditionally. There is no public HTTP reset.

Key rotation = change `DEMO_KEY`.

## Queue workers

Default queue connection is `database` (see `.env` `QUEUE_CONNECTION`). After migrations include `jobs` / `failed_jobs`, run at least one worker:

```bash
php artisan queue:work database --tries=3
```

The Composer `dev` script already runs `queue:listen` alongside `serve` and Vite.

## Mail and queued jobs

Use Laravel Mail / notifications. For **JSON-safe**, **explicit** queued mail, dispatch [`SendCollegePlainMailJob`](../app/Jobs/SendCollegePlainMailJob.php) (scalar payload) instead of serializing arbitrary legacy PHP callables.
