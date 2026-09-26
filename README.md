# FlowEdu

All-in-one ERP built first for Ghana's Colleges of Education, and usable
across tertiary education. One codebase covers:

- Academics: faculties, departments, programs, sessions, timetables
- Finance: fee structures, payments, outstanding balances, scholarships
- Grading: grade points, results entry/upload, approvals, transcripts
- HR and staff: teaching and non-teaching staff, roles, leave requests
- Memos, evaluations, teaching practice, backups, system audit trail

## Portals

Three role-based portals, one login at http://localhost:8000/login:

- Admin: http://localhost:8000/admin/dashboard
- Student: http://localhost:8000/student/dashboard
- Teacher: http://localhost:8000/teacher/dashboard

New installs register the first admin at http://localhost:8000/register
(the system secret defaults to `system_secret`).

## Run locally (Laragon / XAMPP)

Requirements: PHP ^8.3, MySQL, Composer.

```bat
copy .env.example .env
composer install
php artisan key:generate
```

Point `.env` at MySQL (`DB_HOST=127.0.0.1`, `DB_PORT=3306`,
`DB_DATABASE=new_college`, `DB_USERNAME=root`), then:

```bat
php artisan migrate --force
php artisan serve
```

App: http://localhost:8000 (or http://localhost:8000/login).

## Scheduler and queue (Windows)

The scheduler owns evaluation maintenance (hourly), semester status
(hourly), auto-promotion (15th, 03:00), licence retry (04:00) and
heartbeat sync (04:30). The queue (database driver) sends quotes,
leads and notifications. In production both must always run:

- Scheduler: Windows Task Scheduler job running every minute:
  `php artisan schedule:run` with Start-in set to the project directory.
- Queue: a supervised process running
  `php artisan queue:work database --tries=3`.
- Manual runs: `php artisan college:maintenance`,
  `php artisan controlplane:ping`.

## Demo mode

Marketing-only throwaway deployment. Never point it at a production database.

- Set `APP_DEMO=true` with `DB_*` pointed at the demo MySQL database.
- Without `DEMO_KEY`, visitors hit the key screen at
  http://localhost:8000/demo/key (one key per session).
- With `DEMO_KEY` set, the key screen is bypassed entirely.
- Demo shows a banner, one-click demo logins, log-only mail, closed
  registration, and a monthly `demo:refresh` wipe
  (`php artisan demo:refresh`, 1st at 03:00).

## ControlDesk relationship

ControlDesk is the licensing server. Each install is identified by
`DEPLOYMENT_UUID` + `CONTROL_PLANE_TOKEN` in `.env`.

- Set `CONTROL_PLANE_URL` to the ControlDesk base URL.
- Enrollment happens once at setup (Admin Setup, Licence step): redeem a
  claim code, continue offline (provisional, code retried daily at 04:00),
  or import a signed licence file.
- Linked installs heartbeat daily at 04:30 and merge central changes
  (modules frozen to plan; core settings stay locally changeable).
- Offline grace: the app keeps running on the last known licence row;
  pending codes redeem silently when the network returns.

Details: `docs/COLLEGE_OPERATIONS.md`.

## Demo dataset

```bat
php artisan db:seed --class="Database\Seeders\DemoDataSeeder" --force
```

Seeds a full two-year Ghanaian demo dataset (GES academic years anchored
on today): staff and students, fees and payments, results and transcripts,
timetables, memos, evaluations and scholarships. Login hints appear on the
demo login screen; all data resets on `demo:refresh`.

## Tests

```bat
php artisan test
```

Feature suites cover auth, licences and enrollment, demo mode, finance and
admin flows on SQLite; see `phpunit.xml`. Never run the demo seeder or
`demo:refresh` against a real database.
