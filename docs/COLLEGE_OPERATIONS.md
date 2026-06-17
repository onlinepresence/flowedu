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

Tasks: evaluation maintenance (hourly), semester status (hourly), auto-promotion (monthly on the 15th at 03:00). Manual run: `php artisan college:maintenance`.

## Queue workers

Default queue connection is `database` (see `.env` `QUEUE_CONNECTION`). After migrations include `jobs` / `failed_jobs`, run at least one worker:

```bash
php artisan queue:work database --tries=3
```

The Composer `dev` script already runs `queue:listen` alongside `serve` and Vite.

## Mail and queued jobs

Use Laravel Mail / notifications. For **JSON-safe**, **explicit** queued mail, dispatch [`SendCollegePlainMailJob`](../app/Jobs/SendCollegePlainMailJob.php) (scalar payload) instead of serializing arbitrary legacy PHP callables.
