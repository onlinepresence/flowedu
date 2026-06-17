# Mock / placeholder features (sign-off)

Aligned with conversion plan §9.11. **`Route::view(..., 'legacy.placeholder')` has been removed** from `routes/*.php`; every former placeholder route is now a Livewire full page (or existing controller) with real reads from the database where models exist.

## Route parity — `legacy.placeholder` removed

Admin, student, teacher, and `env.generator` routes that previously used the placeholder view now mount Livewire components under `App\Livewire\...`. See `routes/admin.php`, `routes/student.php`, `routes/teacher.php`, and `routes/legacy-public.php`.

Some UIs are still **thin** (e.g. spreadsheet upload = row count only, teacher messages = explicit “no schema” copy). Track deeper CRUD / import pipelines in `ADMIN_SUBMIT_INVENTORY.md`, `SUBMIT_TO_LIVEWIRE_MAP.md`, and `TEACHER_SUBMIT_INVENTORY.md`.

**Admin UX parity (modals, empty states, loading buttons):** see [`ADMIN_ACTION_PARITY.md`](ADMIN_ACTION_PARITY.md) for the shared checklist and test pointers (`ai/ADMIN_COMPLETION.md` section F).

**Role dashboards** (`admin.dashboard`, `student.dashboard`, `teacher.dashboard`) were the remaining shell gap after route parity; they are now full Livewire pages (`AdminDashboardPage`, `StudentDashboardPage`, `TeacherDashboardPage`) backed by real counts and links, not placeholder views.

## P0 backlog (earlier waves) — completed

| Order | Route | Livewire / surface |
|------:|-------|-------------------|
| 1 | `admin.students.index` | `App\Livewire\Admin\Students\StudentIndex` |
| 2 | `admin.grading.enter` | `App\Livewire\Admin\Grading\EnterGradesPage` |
| 3 | `admin.finance.fees` | `App\Livewire\Admin\Finance\FeesIndex` |

## Student admission / profile (implemented)

| Route | Livewire |
|-------|----------|
| `student.setup.personal` | `StudentSetupPersonalPage` |
| `student.setup.guardian` | `StudentSetupGuardianPage` |
| `student.setup.status` | `StudentSetupStatusPage` |
| `student.setup.delete` | `StudentSetupDeletePage` |
| `student.profile` | `StudentProfilePage` |

## Follow-ups (product / depth — not route placeholders)

| Area | Note |
|------|------|
| `student.job-alerts` | Uses `activities` table (legacy parity), not a dedicated jobs schema. |
| `teacher.messages` | Page explains missing messaging schema; no fake threads. |
| Grading / results upload | Admin and teacher upload pages parse spreadsheets for **preview row counts** only until import actions are ported. |
| Admin staff (Section C) | Non-teaching staff, staff assignments/roles, teachers (single create + **Filepond** bulk CSV/XLSX), teacher assignments/roles, and evaluation form delete (inactive + no responses) are implemented under `admin/staff/*`. Teacher bulk template: header row `email, username, lastname, othernames, staff_id, department_id, phone_number`. |
| `env.generator` | Rewrites `.env` from merged keys; comments in `.env` are not preserved (same class of risk as legacy tool). |
