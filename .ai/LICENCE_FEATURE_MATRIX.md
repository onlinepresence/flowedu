# Licence Feature Matrix

Source of truth for **feature keys**, database columns in `school_licences`, and route-level gating in Laravel.

## 1. Feature Mapping & Enforced Paths

| Feature Key | Mapped DB Column | Category | Mapped Routes & Controllers (Enforced Paths) | Description / Notes |
|-------------|------------------|----------|---------------------------------------------|---------------------|
| *(none)* | *(none)* | Core | - `/admin/dashboard`<br>- `/admin/students` (Index, Show, Jobs)<br>- `/admin/academic` (Faculty, Dept, Program, Sessions)<br>- `/admin/grading` (Points, Enter, Upload, Approve, Transcripts)<br>- `/admin/profile`<br>- `/student/dashboard`<br>- `/student/profile`<br>- `/student/courses`<br>- `/student/results`<br>- `/student/transcript`<br>- `/student/job-alerts`<br>- `/student/memos` | Always available under Core Academic licence. Contains basic school setup, student listing, result management, transcript generation, student/teacher dashboards, and public job alerts. |
| `timetable` | `core_timetable` | Core | - `/admin/academic/timetable`<br>- `/student/timetable`<br>- `/teacher/timetable` | Timetable management and viewer for students, teachers, and admins. |
| `attendance` | `core_attendance` | Core | - `/student/attendance`<br>- `/teacher/attendance` (Preview & Sheets) | Lecture and class attendance tracking. |
| `memos` | `core_memos` | Core | - `/admin/memos/*` | Internal administrative memos routing, signatories, and approval workflows. |
| `impersonation` | `core_impersonation` | Core | - `/admin/impersonation` | Allows administrators/owners to impersonate other users for support/troubleshooting. Logs are captured in `admin_impersonation_logs`. |
| `finance` | `module_finance` | Module | - `/admin/finance/*` (Fees, Payments, Outstanding, Scholarships, Allowances)<br>- `/student/allowance`<br>- `/student/scholarships`<br>- `/student/fees`<br>- `/student/payment-history` | Financial ledger, payment logs, fee structures, allowances, and scholarship allocations. |
| `staff_hr` | `module_staff_hr` | Module | - `/admin/staff/*` (except evaluations)<br>- `/teacher/announcements`<br>- `/teacher/messages`<br>- `/teacher/memos`<br>- `/teacher/lesson-plans` | Complete HR directory: administrators list, teaching staff, non-teaching staff, assignments, teacher roles, materials, and communication. |
| `reports` | `module_reports` | Module | - `/admin/reports/*` (Academic, Payments, Attendance, Enrollment, Welfare) | Advanced reports, PDF exports, and graphical analysis. |
| `evaluations` | `module_evaluations` | Module | - `/admin/staff/evaluations` (Form Management & preview)<br>- `/student/evaluation` (Perform evaluation) | Dynamic evaluation forms, student responses, and teacher ratings. |
| `student_welfare` | `module_student_welfare` | Module | - `/admin/students/medical`<br>- `/admin/students/discipline`<br>- `/student/clearance`<br>- `/student/medical`<br>- `/student/discipline` | Student welfare tracking (medical history, disciplinary logs, and graduation clearance). |
| `progression` | `module_progression` | Module | - `/admin/students/promotion`<br>- `/admin/students/graduation` | Academic promotion (advancement to next year level) and graduation processing. |
| `system_admin` | `module_system_admin` | Module | - `/admin/settings/roles`<br>- `/admin/settings/image-validation`<br>- `/admin/settings/users`<br>- `/admin/settings/backup` (and backup downloads)<br>- `/admin/settings/system-preferences`<br>- `/tools/passport-validator` | System-level admin features: role permissions, user list management, backups, image validation configurations, and passport photo validator tool. |
| `teacher_tools` | `module_teacher_tools` | Module | - *(Used in UI to unlock teacher tools)* | Advanced class charts and lesson workflow settings. |
| `messaging` | `module_messaging` | Module | - *(Gated real-time messaging)* | Peer-to-peer messaging system between teachers, students, and admins. Mapped in `2026_06_01_233000_create_messaging_tables.php`. |
| `practicum` | `module_practicum` | Module | - `/admin/practicum/*`<br>- `/student/practicum`<br>- `/teacher/practicum` | Teaching Practice Portal for allocating trainees to supervisors and recording rubrics. |

---

## 2. Enforcement Mechanisms

Gating is handled at different levels depending on request context:
- **Route Gating**: Enforced using the `college.licence:<feature_key>` middleware (registered as `EnsureSchoolLicence::class` in `bootstrap/app.php`). If a check fails, the user is redirected to the `/licence-required` route with an upgrade message.
- **Blade & Logic Gating**: In views, navigation menus (e.g., `layouts/parts/admin-nav.php`, `student-nav.php`), or controllers, features are checked using `licence_can($feature)` helper or `$licenceService->can($feature)`.

---

## 3. Environment Variables & Configurations
Environment settings in `.env` customize licensing rules:
- `LICENCE_ENFORCE`: Set to `false` to bypass all licence checks (useful in development and local demos). Defaults to `true`.
- `STUDENT_CAP_MODE`:
  - `block`: Restricts new student approvals when active student count reaches or exceeds `max_active_students` (Default behavior).
  - `warn`: Allows approvals but displays status warnings on the admin dashboard.
- `LICENCE_CACHE_TTL`: Specifies caching duration (in seconds) for resolved `school_licences` rows to minimize queries. Defaults to `300` seconds.

---

*Document version 3.0 · June 2026*
