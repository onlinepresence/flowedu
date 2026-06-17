# Admin action parity checklist

Cross-cutting UX standards for admin Livewire pages (see `ai/ADMIN_COMPLETION.md` section F). Use this when adding or refactoring list pages and modals.

## Modal primitive

- **Livewire-driven dialogs** (open/close from component state): use `<x-college.modal name="…" :title="…" :show="true" livewireSynced>` with `@if ($show…Modal)` in the parent. Put actions in `<x-slot:footer>`. Long forms can use `form="…"` on the submit button to target a form in the slot body (see `livewire/admin/staff/staff-home-page.blade.php`, `livewire/admin/settings/settings-user-roles-page.blade.php`).
- **Jetstream `x-modal` alone** is still used where Alpine `show` is bound only from the modal component (e.g. some academic pages). Prefer `x-college.modal` for new work so titles and footers stay consistent.
- **Avoid** hand-rolled `fixed inset-0` stacks unless bridging a legacy edge case.

## Empty states

- Tables using `@forelse` / `@empty` should expose a **primary or contextual CTA** when empty (duplicate header action, setup link, clear search, or “use the form above”), not only static text.
- If the primary control is **always visible** above the table (e.g. **Add role** on the roles page), the empty row may repeat that action or rely on the header; document either way in this file when it is intentional.

## Loading labels (`Please wait…` / spinners)

- Reusable buttons: `x-college-submit-button` and `x-college-form-submit`.
- Inline patterns: loading-only `<span wire:loading…>` should include Tailwind **`hidden`** plus **`wire:loading.class.remove="hidden"`** so the label does not flash before Livewire hydrates. Scope with **`wire:target`** matching the action.

## Admin list surfaces (snapshot)

| Route name | Livewire (main) | Modal pattern | Empty-state notes |
|------------|-----------------|---------------|-------------------|
| `admin.staff.*` (teachers, non-teaching, assignments, roles) | Various | `x-college.modal` + `livewireSynced` | Add / empty row CTAs where implemented |
| `admin.settings.roles` | `SettingsUserRolesPage` | `x-college.modal` | Filter empty → **Add role** |
| `admin.settings.users` | `UsersIndexPage` | `x-college.modal` | Search empty → **Clear search** when query non-empty |
| `admin.academic.department` | `DepartmentIndex` | — | Link to `admin.setup.departments` |
| `admin.academic.program` | `ProgramIndex` | — | Link to `admin.setup.programs` |
| `admin.academic.faculty` | `FacultyIndex` (setup) | — | Copy points to add form above |
| `admin.academic.sessions` | `SessionIndex` | `x-modal` | Empty → **Add session** |
| `admin.academic.timetable` | `TimetableIndex` | `x-modal` | No slots → **Add slot** when applicable |
| `admin.students.index` | `StudentIndex` | — | No rows + search → **Clear search** |
| `admin.grading.approve` | `ApproveGradesPage` | — | Empty → links to enter / upload |

Extend this table when you add routes or change primitives.

## Tests (section F smoke)

- Academic session create + approve CTAs + grading enter/upload GET: `tests/Feature/Admin/AdminAcademicSessionsAndGradingTest.php`
- Users impersonation from settings users: `tests/Feature/Admin/UsersIndexImpersonationTest.php`
- Filepond `teacher_import` validation: `tests/Feature/Filepond/FilepondUploadTest.php`
