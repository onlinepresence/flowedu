# Legacy `submit.php` / AJAX → Livewire targets

**Strategy:** Livewire 3 + Alpine first; JSON only as a short-lived strangler. See [COLLEGE_HTTP_CSRF.md](./COLLEGE_HTTP_CSRF.md) and [AJAX_INVENTORY.md](./AJAX_INVENTORY.md).

## Admin (`admin/submit.php`)

| Legacy area | Planned Livewire / Laravel surface |
|-------------|-----------------------------------|
| Sessions CRUD | `App\Livewire\Admin\Academic\SessionIndex` + actions (to be created) |
| Faculties / departments / programs | Extend `FacultyIndex` pattern → dedicated index components per entity |
| Staff / teachers | `App\Livewire\Admin\Staff\...` |
| Grading (enter scores) | `App\Livewire\Admin\Grading\EnterGradesPage` + `UpdateResultScoreRequest` + `UpdateResultScoreAction` |
| Grading uploads | `App\Livewire\Admin\Grading\...` + `phpoffice/phpspreadsheet` services |
| Settings / backup | `App\Livewire\Admin\Settings\BackupIndex` (history) + future `BackupRunAction` |
| JSON `response_type=json` | Remove once UI is Livewire; do not add permanent parallel JSON |
| **Student promotion / graduation / medical / discipline** | **`PromotionIndexPage`** + `ManualPromotionService`; **`GraduationIndexPage`** + `ProcessGraduationService`; **`MedicalRecordsPage`**; **`DisciplineRecordsPage`** — see [ADMIN_SUBMIT_INVENTORY.md](./ADMIN_SUBMIT_INVENTORY.md) “Mapped — admin students”. |

## Student (`student/submit.php`)

| Legacy area | Planned surface |
|-------------|-----------------|
| Admission / profile / guardian / activate / delete | `StudentSetupPersonalPage`, `StudentSetupGuardianPage`, `StudentSetupStatusPage`, `StudentSetupDeletePage`, `StudentProfilePage` + `App\Http\Requests\Student\*` + `App\Actions\Students\*` (see `STUDENT_SUBMIT_INVENTORY.md`) |
| Clearance updates | Extend `StudentClearancePage` or sibling component when DB writes exist |

## Teacher (`teacher/submit.php`)

| Legacy area | Planned surface |
|-------------|-----------------|
| Onboarding / password | `TeacherSetupWizard` + dedicated profile Livewire |
| Materials / results upload | `App\Livewire\Teacher\...` + storage + validation |

## AJAX scripts (`admin/ajax/*.php`)

Use [AJAX_INVENTORY.md](./AJAX_INVENTORY.md) as the branch list. Each `submit=` value should gain a row in a spreadsheet or this doc: **Livewire component + method** (preferred) or **temporary** invokable controller (delete after port).

## Named routes

`tightenco/ziggy` and `@routes` were removed: no `resources/js` caller uses `route()` from Ziggy. Use `route()` in Blade / Livewire PHP only; if a future bundled script needs named URLs, pass URLs from the server (e.g. `data-*` attributes) or reintroduce Ziggy deliberately.
