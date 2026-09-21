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
| `system_admin` | `/admin/settings/roles`, `image-validation`, `backup`, `/env-generator` | Those settings links hidden (`/tools/*` is middleware-only; not in legacy URL map) |

User Accounts (`/admin/settings/users`) is core functionality, not a licensed module: it is permission-gated (`nav_settings_users`) on every tier. Dashboard links and stats-card targets are likewise hidden unless their route's licence module allows (finance, welfare, staff HR, evaluations, system admin).

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

### Trial-to-live elevation (no demo mode involved)

A trial that already holds real data elevates inside normal setup: when the
licence step detects existing records (any students, or more than the single
installing owner account), it offers **Continue with existing data and
activate** next to guidance-only **Start fresh** (reinstall separately, then
redeem — not implemented as an action). The primary path takes a database
backup through the Backup path FIRST and aborts before touching anything if
it fails; then redeems the code, replaces the licence row, clears
provisional, and voids any local demo key (`DEMO_KEY` line removed from
`.env`, key session forgotten). One-way enforced: linked (live) installs
refuse demo keys outright, with a warning-level log.

Linked installs (`DEPLOYMENT_UUID` set and matching the row) render the
licence step **read-only** ("Managed by ControlDesk"); provisional installs
keep local editing badged **PROVISIONAL**; installs with neither behave
exactly as before. Heartbeats go to `POST {CONTROL_PLANE_URL}/api/v1/heartbeats`
(Bearer token) with `{deployment_uuid, product: "flowedu", app_version,
counts{students, teachers, users}, modules_in_use[]}`. Test without the
wizard: `php artisan controlplane:ping --redeem CODE`.

## Sales leads (landing quote → ControlDesk)

Every landing quote submit ALSO posts
`{product_slug: "flowedu", contact{name, role, phone, email, college}, band,
modules[], quote{upfront, renewal, lines}}` to
`POST {CONTROL_PLANE_URL}/api/v1/leads` (no auth — the slug identifies
against the products table; unknown/inactive slug → 404, logged once, never
retried). The post runs in the queued `PostLeadToControlDeskJob` (3 tries,
backoff 60s/5m/15m, silent failure) so the quote response never waits on it,
and the admin notification email still goes out regardless as fallback.
Phone/email leads that never touch the form get entered manually in
ControlDesk. The public form carries Cloudflare Turnstile armor
(`TURNSTILE_SITE_KEY`/`TURNSTILE_SECRET_KEY`; skipped when unset).

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

> `APP_DEMO` is for marketing demo deployments ONLY — a throwaway database
> seeded with sample data. Never point it at a school production database:
> banners, open credentials hints, closed registration and the monthly
> `demo:refresh` wipe are all designed around disposable data.

When `APP_DEMO=true`: demo banner on all pages, demo credentials hint on login,
mail forced to `log` driver, public registration closed (seeded users only).
Licence enforcement STAYS ON — the seeded `school_licences` row governs features.

Key gate: `DEMO_KEY` env bypasses the key-entry screen entirely (the hosted instance
lives here). Without it, visitors see `GET /demo/key` (403-style, FlowEdu landing
look, Alpine form, no Livewire) and must enter a key once per session
(`session('demo_key_accepted')`). The screen takes two inputs: a pasted signed
document, or a bare code (verified online once, then cached). Validation lives in
[`DemoKeyVerifier`](../app/Services/DemoKeyVerifier.php) and is offline-first —
documents look like
`{"payload": {"expires_at": "2027-09-18"|null, "host": "demo.example.com"|null,
"issued_at": "2026-09-19"}, "signature": "<hex ed25519>", "algorithm": "ed25519"}`,
verified against the baked-in `DEMO_PUBLIC_KEY` with no network involved. A bare
code is POSTed once to ControlDesk `/api/v1/demo-keys/verify`; the returned
document is cached locally (`demo.cached_key_document` setting) and every later
check — including offline ones — runs against that cached signature. Offline with
no cache stays on the key screen, as always. A null `expires_at` means NEVER
(the marketing key): accepted, but logged at warning level so it stays visible.
Bad signatures (tamper) and issued-in-the-future documents (clock suspect) fail
into the enforced key screen with a warning-level log; expired, wrong-host and
malformed keys fail quieter with per-reason screen copy. A bare opaque token
(e.g. a ControlDesk heartbeat token pasted at the wrong door) gets its own error
telling the visitor those belong in `.env`, not here. Linked (live) installs
refuse demo keys outright — elevation never flows back to demo.

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
set `APP_DEMO=true`, `DEMO_KEY=<signed-key>` and `DEMO_PUBLIC_KEY=<base64-ed25519-pubkey>`,
then `php artisan migrate --force`
plus `php artisan db:seed --class="Database\Seeders\DemoDataSeeder" --force`.

### Monthly refresh

`php artisan demo:refresh` runs `migrate:fresh` on the DEFAULT connection plus
`DemoDataSeeder`. Its only restriction is `APP_DEMO=true` in env (no database-name
check). [`routes/console.php`](../routes/console.php) registers it
(`demo-refresh-monthly`, monthly on the 1st at 03:00) ONLY when `APP_DEMO` is true —
never unconditionally. There is no public HTTP reset.

On success the entry persists before redirect: the plain value (document-JSON
input → compact canonical document string; bare-code input verified online →
the code itself) is encoded to a single opaque `DEMO_KEY` token in `.env`
(via `EnvWriter`, verified write) so the stored value is not guessable, and the
verified document is cached (`demo.cached_key_document` for offline rechecks).
Each hit with a decodable env value hydrates the session flag, so the pass
rides the browser session. Precedence: env bypass first, then session, then
cached document. A failed `.env` write never blocks a valid key — session-only
pass plus a one-time warning. Heartbeat/wrong-door tokens are never persisted.
Key rotation = change the `DEMO_KEY` value (old keys lapse at their `exp`); change
`DEMO_KEY` to rotate the hosted bypass.

## Queue workers

Default queue connection is `database` (see `.env` `QUEUE_CONNECTION`). After migrations include `jobs` / `failed_jobs`, run at least one worker:

```bash
php artisan queue:work database --tries=3
```

The Composer `dev` script already runs `queue:listen` alongside `serve` and Vite.

## Mail and queued jobs

Use Laravel Mail / notifications. For **JSON-safe**, **explicit** queued mail, dispatch [`SendCollegePlainMailJob`](../app/Jobs/SendCollegePlainMailJob.php) (scalar payload) instead of serializing arbitrary legacy PHP callables.
