# Licence feature matrix

Source of truth for **feature keys** → **minimum package tier** and **where enforced**.

| Feature key | Minimum tier | Admin routes / nav | Student routes | Notes |
|-------------|--------------|--------------------|----------------|-------|
| *(none)* | Core | Dashboard, students list/print, academic, grading, setup school profile | Dashboard, profile, courses, timetable, results, transcript, attendance | Always available at Core. |
| `finance` | Professional | `/admin/finance/*` | `/student/fees`, `/student/payment-history`, `/student/allowance` | Fee structures, payments, scholarships. |
| `reports` | Professional | `/admin/reports/*` | — | Academic, payment, attendance reports. |
| `staff` | Professional | `/admin/staff/*` except evaluation URLs | — | Admin staff, teachers, assignments, materials, announcements. |
| `evaluations` | Complete | `/admin/staff/evaluations`, `/admin/staff/evaluation/*`, preview demo | `/student/evaluation*`, perform evaluation | Teacher evaluation module. |
| `student_professional` | Professional | `/admin/students/promotion`, `graduation`, `medical`, `discipline` | `/student/clearance`, `/student/medical`, `/student/discipline` | Promotion, graduation, medical & discipline per Professional package. |
| `system_admin` | Complete | `/admin/settings/roles`, `users`, `image-validation`, `backup`, `/env-generator`, `/tools/*` | — | Roles, users, passport validation, backup, env generator, tools. |

Constants and `licence_can()` live in `includes/licence.php`. Route-level gating uses the `licence` key on grouped routes in `routes.php` (merged from parent groups when omitted). Navigation is filtered in `layouts/parts/admin-nav.php` and `layouts/parts/student-nav.php`.

**Environment**

- `LICENCE_ENFORCE` — when `false`, all `licence_can()` checks succeed (demos / local dev).
- `STUDENT_CAP_MODE` — `block` (default) prevents approving students when active count ≥ cap; `warn` shows dashboard warning but does not block approval.
