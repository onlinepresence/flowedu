# Phase C–E §8 mining loop

Use this checklist whenever you implement an admin, student, or teacher feature slice so legacy behaviour stays traceable (plan §8 + §11).

For each **feature slice** (e.g. “admin faculties”, “student fees”, “teacher materials”):

1. **Route / licence** — In legacy [`routes.php`](../../routes.php), find the URI, `middleware` list, and `licence` key. Match or extend [`new-college/routes/admin.php`](../routes/admin.php), [`student.php`](../routes/student.php), or [`teacher.php`](../routes/teacher.php).
2. **Page** — Open the legacy PHP under [`admin/`](../../admin), [`student/`](../../student), or [`teacher/`](../../teacher) (or [`pages/`](../../pages)) and note UI + queries.
3. **POST hub** — If the form posts to `submit.php`, record the `submit` value in [`ADMIN_SUBMIT_INVENTORY.md`](ADMIN_SUBMIT_INVENTORY.md), [`STUDENT_SUBMIT_INVENTORY.md`](STUDENT_SUBMIT_INVENTORY.md), or [`TEACHER_SUBMIT_INVENTORY.md`](TEACHER_SUBMIT_INVENTORY.md) if not already listed; implement as Form Request + Action / Livewire.
4. **AJAX** — If the page uses [`admin/ajax/`](../../admin/ajax) or [`student/ajax/`](../../student/ajax), add or update a row in [`AJAX_INVENTORY.md`](AJAX_INVENTORY.md).
5. **Includes** — For shared rules, mine [`includes/functions.php`](../../includes/functions.php), [`includes/helpers.php`](../../includes/helpers.php), [`includes/database_functions.php`](../../includes/database_functions.php), [`includes/form-validation.php`](../../includes/form-validation.php), and domain includes (e.g. [`includes/student_function.php`](../../includes/student_function.php)) into Laravel **services**, **policies**, or **custom validation rules**.
6. **UI parity** — Compare legacy [`includes/components.php`](../../includes/components.php) / [`question-components.php`](../../includes/question-components.php) with Blade/Livewire components; no separate index required unless a gap list is useful for QA.

**Commit discipline:** In the commit message or PR description, reference the legacy path (e.g. `Ports admin/academic/faculties.php + admin/submit.php create_faculty`) so history links code to §8 sources.

**Phase mapping (plan §7):**

| Phase | Focus | Primary §8 paths |
|-------|--------|------------------|
| C | Admin modules | `admin/*`, `admin/submit.php`, `admin/ajax/*`, `includes/settings_functions.php` |
| D | Student app | `student/*`, `student/submit.php`, `student/ajax/*` |
| E | Teacher app | `teacher/*`, `teacher/submit.php` |

When a slice is **explicitly out of scope**, record the waiver in the main plan §9 backlog or product notes—do not leave silent gaps.
