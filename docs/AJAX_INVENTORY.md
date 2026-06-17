# Legacy AJAX inventory → Laravel targets

See also [SUBMIT_TO_LIVEWIRE_MAP.md](./SUBMIT_TO_LIVEWIRE_MAP.md) for `submit.php` decomposition and Livewire-first strategy.

Legacy endpoints live under [admin/ajax/](../../admin/ajax/) and [student/ajax/courses.php](../../student/ajax/courses.php). Callers typically POST with `submit=<action>`, `response_type=json`, and optional filters (pagination uses `page`, etc. via `form_data()`).

## Standard JSON envelope

Most admin AJAX scripts respond with:

```json
{
  "errors": {},
  "old_input": { },
  "status": true,
  "data": { }
}
```

[`teacher/submit.php`](../../teacher/submit.php) and [`student/submit.php`](../../student/submit.php) may return `message` instead of or alongside `data` when `response_type=json`.

**Laravel direction:** prefer **Livewire** (no ad-hoc JSON) for new UI; where legacy JS must coexist temporarily, add explicit **`Route::post`** controller methods or invokables that return the same shape, then remove when the Blade/JS client is gone (plan §9.3). A stub exists: [`college.ajax.ping`](../routes/web.php).

## `admin/ajax/academic.php`

| `submit` | Purpose / `data` keys | Status |
|----------|----------------------|--------|
| `fetch_timetables` | Paginated timetables + totals; filters `program_id`, `level`, `page` | **Deferred** — [`TimetableIndex`](../app/Livewire/Admin/Academic/TimetableIndex.php) lists timetables server-side; legacy JSON contract not ported. |
| `fetch_timetable_classes` | `timetable_id` → `classes` | **Deferred** — same as above. |
| `fetch_timetable_courses` | Courses for timetable builder | **Deferred** — same as above. |
| `fetch_timetable_teachers` | Teachers for timetable builder | **Deferred** — same as above. |
| `delete_timetable_class` | Remove one class slot | **Deferred** — same as above. |
| `load_timetable` / `create_timetable` | Load or create timetable grid | **Deferred** — same as above. |

## `admin/ajax/admin.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_admins` | Paginated admin list (joins `admins`, `users`, `user_roles`) | **Partial** — [`UsersIndexPage`](../app/Livewire/Admin/Settings/UsersIndexPage.php) lists users; legacy admin-directory JSON not ported. |

## `admin/ajax/session.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_sessions` | List academic sessions | **Deferred** — [`SessionIndex`](../app/Livewire/Admin/Academic/SessionIndex.php) lists sessions server-side; session CRUD + this JSON action not ported. |
| `add_session` | Create session | **Deferred** — no session CRUD UI in new-college yet. |
| `update_session` | Update session | **Deferred** — same. |
| `delete_session` | Delete session | **Deferred** — same. |

## `admin/ajax/school.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_faculties` | Paginated faculties | **Implemented (Livewire)** — [`App\Livewire\Admin\Academic\FacultyIndex`](../app/Livewire/Admin/Academic/FacultyIndex.php) (`admin.academic.faculty` / setup faculties). |
| `fetch_departments` | Paginated departments | **Partial** — setup flow: [`SetupDepartmentPage`](../app/Livewire/Admin/Setup/SetupDepartmentPage.php); main app: [`DepartmentIndex`](../app/Livewire/Admin/Academic/DepartmentIndex.php) (read-only list). |
| `fetch_programs` | Paginated programs | **Partial** — setup: [`SetupProgramPage`](../app/Livewire/Admin/Setup/SetupProgramPage.php); main app: [`ProgramIndex`](../app/Livewire/Admin/Academic/ProgramIndex.php) + program detail routes. |
| `fetch_courses` | Paginated courses | **Deferred** — no course catalog Livewire index yet. |
| `fetch_roles` | Paginated `user_roles` | **Partial** — [`SettingsUserRolesPage`](../app/Livewire/Admin/Settings/SettingsUserRolesPage.php) and [`InstitutionRolesPage`](../app/Livewire/Admin/Staff/InstitutionRolesPage.php) list roles server-side; JSON + CRUD not ported. |

## `admin/ajax/teacher.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_teachers` | Paginated teachers | **Partial** — [`TeacherListPage`](../app/Livewire/Admin/Staff/TeacherListPage.php) lists teachers server-side; legacy JSON contract not ported. |

## `admin/ajax/student.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_students` | Paginated students | **Implemented (Livewire)** — [`App\Livewire\Admin\Students\StudentIndex`](../app/Livewire/Admin/Students/StudentIndex.php) with search + pagination (not JSON). |
| `download_students` | Export-style response | **Deferred** — no export action wired. |
| `fetch_unapproved_students` | Admission queue | **Partial** — unapproved flow via [`ApproveStudentPage`](../app/Livewire/Admin/Students/ApproveStudentPage.php) + student list filters; not the legacy JSON contract. |
| `fetch_promotions` | Promotion listing | **Implemented (Livewire)** — [`PromotionIndexPage`](../app/Livewire/Admin/Students/PromotionIndexPage.php) history table + manual bulk flow; legacy JSON list not used. |
| `promote_student` | Single promotion action | **Deferred** — bulk manual promotion covers typical use; single-student shortcut not exposed. |
| `get_graduation_stats` | Stats JSON | **Implemented (Livewire)** — [`GraduationIndexPage`](../app/Livewire/Admin/Students/GraduationIndexPage.php) renders totals + “this year”; no JSON endpoint. |
| `fetch_graduated_students` | Graduated list | **Implemented (Livewire)** — same page, program filter on list. |
| `fetch_graduations` | Graduation records | **Implemented (Livewire)** — paginated `graduations` on [`GraduationIndexPage`](../app/Livewire/Admin/Students/GraduationIndexPage.php). |
| `graduate_student` | Mark graduated | **Implemented (Livewire)** — `processGraduation()` batch; single-student AJAX not ported. |
| `search_students` | Search | **Partial** — covered by `StudentIndex` `$search` (Livewire), not POST JSON. |
| `search_medical_students` | Medical module | **Implemented (Livewire)** — [`MedicalRecordsPage`](../app/Livewire/Admin/Students/MedicalRecordsPage.php) debounced picker search; no JSON. |
| `get_medical_student` | Single record | **Implemented (Livewire)** — selection loads student + `medical_histories` into the form. |
| `fetch_disciplinary_records` | Discipline list | **Implemented (Livewire)** — [`DisciplineRecordsPage`](../app/Livewire/Admin/Students/DisciplineRecordsPage.php) filtered paginated table. |
| `resolve_disciplinary_record` | Resolve | **Implemented (Livewire)** — `closeRecord()` on same page. |
| `fetch_student_clearances` | Clearance rows | **Deferred** — admin clearance list not ported; student side has [`StudentClearancePage`](../app/Livewire/Student/StudentClearancePage.php) only. |

## `admin/ajax/grading.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_grade_points` | Grade scale | **Partial** — [`GradePointsPage`](../app/Livewire/Admin/Grading/GradePointsPage.php) lists and adds bands; legacy JSON not ported. |
| `fetch_results` | Results grid | **Partial** — [`EnterGradesPage`](../app/Livewire/Admin/Grading/EnterGradesPage.php) lists `Result` rows per session with pagination (Livewire). |
| `fetch_course_students` | Students for a course | **Deferred** — not exposed as separate JSON; results grid embeds student/course via relations. |
| `fetch_transcripts` | Transcript list | **Deferred** — print route exists for single transcript; list JSON not ported. |
| `download_transcript` | File/stream | **Partial** — [`PrintRecordController::transcript`](../app/Http/Controllers/Admin/PrintRecordController.php) (`admin.grading.transcripts`). |
| `download_template` | Spreadsheet template | **Deferred** — [`UploadGradesPage`](../app/Livewire/Admin/Grading/UploadGradesPage.php) analyzes uploads only; template download not wired. |
| `calculate_gpa` | GPA JSON | **Deferred** — no GPA calculator endpoint. |

## `admin/ajax/finance.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_fee_structures` | Fee structures | **Deferred** — no fee structure CRUD Livewire yet. |
| `fetch_payments` | Payments | **Partial** — [`FeesIndex`](../app/Livewire/Admin/Finance/FeesIndex.php) lists `FeePayment` rows (`admin.finance.fees`); not legacy JSON. |
| `fetch_outstanding_fees` | Outstanding | **Partial** — [`FinanceOutstandingPage`](../app/Livewire/Admin/Finance/FinanceOutstandingPage.php); JSON not ported. |
| `fetch_scholarships` | Scholarships | **Partial** — [`ScholarshipsIndexPage`](../app/Livewire/Admin/Finance/ScholarshipsIndexPage.php); JSON not ported. |
| `fetch_scholarship_recipients` | Recipients | **Deferred** — same. |

## `admin/ajax/evaluation.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_evaluation_forms` | Forms list | **Implemented (Livewire)** — [`EvaluationIndexPage`](../app/Livewire/Admin/Staff/EvaluationIndexPage.php) loads forms server-side (`admin.evaluations`). |

## `admin/ajax/reports.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `generate_academic_report` | Report payload | **Partial** — [`AcademicReportPage`](../app/Livewire/Admin/Reports/AcademicReportPage.php) shows aggregates; legacy JSON export not ported. |
| `generate_payment_report` | Report payload | **Partial** — [`PaymentReportPage`](../app/Livewire/Admin/Reports/PaymentReportPage.php); legacy JSON export not ported. |
| `generate_attendance_report` | Report payload | **Partial** — [`AttendanceReportPage`](../app/Livewire/Admin/Reports/AttendanceReportPage.php); legacy JSON export not ported. |

## `student/ajax/courses.php`

| `submit` | Purpose | Status |
|----------|---------|--------|
| `fetch_my_courses` | `program_id`, `search`, `limit`, `page` → `courses`, `total` | **Deferred** — no student course-picker Livewire/API in new-college yet. |

---

When porting a screen, add a row to the relevant **Livewire component docblock** or extend [ADMIN_SUBMIT_INVENTORY.md](ADMIN_SUBMIT_INVENTORY.md) / [STUDENT_SUBMIT_INVENTORY.md](STUDENT_SUBMIT_INVENTORY.md) if the AJAX action is folded into the same feature.
