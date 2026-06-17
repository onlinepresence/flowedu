# Final UI primitive audit (merge gate)

Run after major Livewire ports. Goal: **no large islands of duplicate utility markup** for the same UI concept.

## 1. Inventory (`resources/views/components/`)

- [ ] Buttons: `primary-button`, `secondary-button`, `danger-button`, `college-submit-button` (Livewire `wire:click`), `college-form-submit` (Livewire `wire:submit` + `target`)
- [ ] Loading: `skeleton-line`, `skeleton-table-rows` (tables / live search)
- [ ] Form: `input-label`, `text-input`, `input-error`, `select-input`, `textarea-input`
- [ ] Layout: `layouts.college-shell`, `layouts.admin`, `layouts.student`, `layouts.teacher`
- [ ] Surfaces: `card`, `modal`, `licence-required-panel`
- [ ] Note gaps (e.g. missing `checkbox-input`, `file-input`)

## 2. Grep for smelly patterns

Examples to find stragglers:

- Repeated `rounded-lg border border-gray-200 bg-white shadow-sm` outside `<x-card>`
- Raw `<button class="...bg-indigo-600` without `college-submit-button` / Breeze buttons where appropriate
- Inline Heroicons SVG where Font Awesome is standard for new work

## 3. Replace

- [ ] Refactor matches to use existing components or add a **single** new primitive
- [ ] Keep dark-mode classes consistent with existing components

## 4. Verify

- [ ] Admin, student, teacher shells load without layout regressions
- [ ] At least one form uses `select-input` / `textarea-input` when those controls appear

## 5. Skeletons + loading buttons (Livewire)

### When to use

- [ ] **Skeletons:** tables driven by `wire:model.live` (search/filter) or heavy refetch (e.g. session selector); paginated lists where jank is visible under throttled network. Use `x-skeleton-table-rows` (and `x-skeleton-line` for ad-hoc blocks). Pair with `wire:loading.remove.delay` on the real `<tbody>` and `wire:loading.delay` on a skeleton `<tbody>` (valid HTML: multiple `tbody`). Scope `wire:target` when the same view has other actions (e.g. per-row save).
- [ ] **Submit / action buttons:** any `wire:submit` → `x-college-form-submit` with `target` = method name (`save`, `login`, `saveFaculty`, …). Variants: `indigo` (default), `purple` (evaluations), `danger` (destructive submit), `auth` (Breeze-style guest/profile gray).
- [ ] **`wire:click` single action:** `x-college-submit-button` with `action="methodName"` (and `variant` if needed). **Scoped** `wire:target` is built into these components—do not drop it on multi-action pages.
- [ ] **Spinner:** Font Awesome `fa-solid fa-spinner fa-spin` + `__('Please wait…')` (or existing copy) with `wire:loading.delay.200ms` aligned with `college-submit-button`.
- [ ] **Exceptions:** `layout` logout (`wire:click="logout"`) may stay without spinner if redirect is effectively instant; read-only Livewire pages (lists with only `wire:navigate` / `wire:key`) → mark **N/A** in audit. `legacy.placeholder` routes out of scope.

### Grep (merge gate)

```bash
rg "wire:submit|wire:click" resources/views/livewire --glob "*.blade.php"
rg "wire:loading" resources/views/livewire --glob "*.blade.php"
```

Flag lines with `wire:submit` / `wire:click` but no nearby `wire:loading` / primitive.

### Audit table (Livewire views)

| Path | Skeleton? | Button loading? | Notes | Done |
|------|-----------|-----------------|-------|------|
| `livewire/admin/students/student-index` | Y (search + any request) | N/A | Dual `tbody` skeleton | Y |
| `livewire/admin/settings/users-index-page` | Y | N/A | Same pattern as student index | Y |
| `livewire/admin/grading/enter-grades-page` | Y (`academicSessionId,gotoPage`) | Y per-row `saveResult` | Skeleton excludes row save | Y |
| `livewire/admin/grading/approve-grades-page` | Optional paginate | Y | Per-row approve/reject | Y |
| `livewire/admin/grading/grade-points-page` | Optional paginate | Y | `saveRow` | Y |
| `livewire/admin/grading/upload-grades-page` | N | Y | `analyze` → `college-submit-button` | Y |
| `livewire/admin/settings/school-profile-form` | N | Y | `save` | Y |
| `livewire/admin/settings/backup-index` | N | Y | Spinner + Please wait; scoped backup/restore | Y |
| `livewire/admin/settings/licence-settings-page` | Optional (`package_tier` live) | Y | `save` | Y |
| `livewire/admin/settings/image-validation-page` | N | Y | `validateUpload` | Y |
| `livewire/admin/tools/passport-validator-page` | N | Y | `validatePassport` | Y |
| `livewire/admin/tools/env-generator-page` | N | Y | `save` | Y |
| `livewire/admin/academic/faculty-index` | Optional | Y | `saveFaculty` | Y |
| `livewire/admin/setup/*` (department, hall, program, licence, personal) | Optional | Y | Form submits | Y |
| `livewire/admin/setup/setup-activate-page` | N | Y | `setReady(true/false)` scoped | Y |
| `livewire/admin/staff/evaluation-index-page` | N | Y | `createForm` purple | Y |
| `livewire/admin/staff/evaluation-manage-page` | N | Y | `saveDetails`, `addQuestion`, `removeQuestion` | Y |
| `livewire/admin/students/approve-student-page` | N | Y | Existing `college-submit-button` | Y |
| `livewire/admin/impersonation/impersonation-index-page` | N | Y | Per-user `impersonate(id)` | Y |
| `livewire/teacher/teacher-setup-wizard` | N | Y | `save` | Y |
| `livewire/teacher/teacher-results-upload-page` | N | Y | `analyze` | Y |
| `livewire/student/student-setup-*`, `student-profile-page` | N | Y | Form submits / activate | Y |
| `livewire/student/student-evaluation-perform-page` | N | Y | `submit`, `saveDraft` | Y |
| `livewire/pages/auth/*` (Volt) | N | Y | `college-form-submit` / `college-submit-button` + `auth` variant | Y |
| `livewire/profile/*` | N | Y | Profile save, password, delete modal, resend verification | Y |
| `livewire/layout/navigation`, `logout-button` | N | N/A | Logout—exception unless slow | — |
| Other `livewire/**` list/report/read-only pages | Optional first paint | N/A | Pagination-only: add skeleton if throttle shows jank | — |

### Browser verify (throttle)

- [ ] Student index search + pagination; Users index; Enter grades session change + save row; Approve/reject; Backup; Impersonation; School profile save; Auth login; Evaluation manage (two forms + remove).

Update this table when adding Livewire pages or changing actions.
