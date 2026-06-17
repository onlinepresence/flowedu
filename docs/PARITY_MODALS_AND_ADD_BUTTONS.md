# Parity: modals, “Add” actions, and list vs legacy

This document cross-walks **Laravel full-page routes** in [`routes/admin.php`](../routes/admin.php), [`routes/student.php`](../routes/student.php), and [`routes/teacher.php`](../routes/teacher.php) against **legacy** entries in repo-root [`routes.php`](../../routes.php).

**Legend**

| Code | Meaning |
|------|---------|
| **L** | Legacy is primarily list / read |
| **C** | Legacy has create / add (form or submit) |
| **M** | Legacy has modal or inline view/edit panel for a row |

**Priority**: **P0** = blocks daily parity; **P1** = high-value workflow; **P2** = medium; **P3** = low / nice-to-have.

Submit/AJAX detail lives in [`ADMIN_SUBMIT_INVENTORY.md`](./ADMIN_SUBMIT_INVENTORY.md) and [`AJAX_INVENTORY.md`](./AJAX_INVENTORY.md).

---

## Admin — core (`/admin`)

| Route name | Laravel surface | Legacy `routes.php` file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|---------------------------|-------|---------------------|----------|
| `admin.impersonation.index` | `ImpersonationIndexPage` | *(no legacy route in `routes.php`)* | — | Laravel-only feature | P3 |
| `admin.dashboard` | `AdminDashboardPage` | `admin/dashboard.php` | L | Compare widgets/links | P2 |
| `admin.approve-student` | `ApproveStudentPage` | `admin/approve-student.php` | C | Track parity with legacy approval rules | P2 |
| `admin.students.print` | `PrintRecordController@student` | `admin/pages/students/print.php` | M | Print layout parity | P2 |
| `admin.students.index` | `StudentIndex` | `admin/pages/students/index.php` | L/M/C | Export, faculty/dept/program filters, in-page view/edit modal | P1 |
| `admin.profile` | `AdminSetupPersonalPage` | `admin/setup/personal.php` | C | Same component as setup; verify field parity | P2 |

---

## Admin — students (`/admin/students`, `student_professional`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.students.promotion` | `PromotionIndexPage` | `admin/pages/students/promotion.php` | L/C | **Implemented:** session gate, mode, manual preview/confirm, history. Auto worker remains `AutoPromotionService` (no “run now” button). | — |
| `admin.students.graduation` | `GraduationIndexPage` | `admin/pages/students/graduation.php` | L/C/M | **Implemented:** process form, stats, graduated list + program filter. **Deferred:** admin bulk clearance UI (see dashed panel on page). | P1 (clearance) |
| `admin.students.medical` | `MedicalRecordsPage` | `admin/pages/students/medical.php` | L/C/M | **Implemented:** search → edit → save (student + `medical_histories`). Browse list retained. | — |
| `admin.students.discipline` | `DisciplineRecordsPage` | `admin/pages/students/discipline.php` | L/C | **Implemented:** add record, filters, close case, richer table. | — |

---

## Admin — setup (`/admin-setup`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.setup.personal` | `AdminSetupPersonalPage` | `admin/setup/personal.php` | C | — | P3 |
| `admin.setup.school` | `SchoolProfileForm` | `admin/setup/school.php` | C | — | P3 |
| `admin.setup.licence` | `SetupLicenceForm` | `admin/setup/licence.php` | C | — | P3 |
| `admin.setup.programs` | `SetupProgramPage` | `admin/setup/program.php` | C | — | P3 |
| `admin.setup.halls` | `SetupHallPage` | `admin/setup/hall.php` | C | — | P3 |
| `admin.setup.departments` | `SetupDepartmentPage` | `admin/setup/department.php` | C | — | P3 |
| `admin.setup.faculties` | `FacultyIndex` | `admin/setup/faculty.php` | C | Inline add vs legacy | P3 |
| `admin.setup.activate` | `SetupActivatePage` | `admin/setup/activate.php` | C | — | P3 |

---

## Admin — staff (`/admin/staff`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.staff.index` | `StaffHomePage` | `admin/pages/staff/index.php` | L | Hub parity | P2 |
| `admin.evaluations` | `EvaluationIndexPage` | `admin/pages/staff/evaluation/index.php` | L/C | Add form / modals per legacy | P2 |
| `admin.evaluation.preview` | `EvaluationPreviewPage` | `pages/preview-evaluation.php` | M | — | P2 |
| `admin.evaluation` | `EvaluationManagePage` | `admin/pages/staff/evaluation/manage.php` | L/C/M | Tabs, questions, modals | P1 |
| `admin.staff.teachers` | `TeacherListPage` | `admin/pages/staff/teachers.php` | L/C/M | Add/edit teacher modals | P1 |
| `admin.staff.non-teaching` | `NonTeachingListPage` | `admin/pages/staff/non-teaching.php` | L/C/M | Same | P1 |
| `admin.staff.assignments` | `StaffAssignmentsListPage` | `admin/pages/staff/assignments.php` | L | Legacy redirect note | P3 |
| `admin.staff.teacher-assignments` | `TeacherAssignmentsPage` | `admin/pages/staff/teacher-assignments.php` | L/C | Assign workflows | P1 |
| `admin.staff.teacher-roles` | `TeacherRoleListPage` | `admin/pages/staff/teacher-roles.php` | L/C | Role edits | P2 |
| `admin.staff.staff-assignments` | `StaffAssignmentsListPage` | `admin/pages/staff/staff-assignments.php` | L/C | — | P2 |
| `admin.staff.staff-roles` | `StaffRoleListPage` | `admin/pages/staff/staff-roles.php` | L/C | — | P2 |
| `admin.staff.roles` | `InstitutionRolesPage` | `admin/pages/staff/roles.php` | L/C | Legacy compatibility route | P2 |
| `admin.staff.materials` | `CourseMaterialsPage` | `admin/pages/staff/materials.php` | L/C | Upload / manage | P2 |
| `admin.staff.announcements` | `AnnouncementsStaffPage` | `admin/pages/staff/announcements.php` | L/C | Post / edit | P2 |

---

## Admin — academic (`/admin/academic`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.academic.faculty` | `FacultyIndex` | `admin/setup/faculty.php` | C | — | P3 |
| `admin.academic.department` | `DepartmentIndex` | `admin/setup/department.php` | C | — | P3 |
| `admin.academic.program` | `ProgramIndex` | `admin/setup/program.php` | C | — | P3 |
| `program.classes` | `ProgramClassesPage` | `admin/pages/course.php` | L/C/M | Class/course management modals | P1 |
| `program.manage` | `ProgramManagePage` | `admin/pages/course.php` | L/C/M | Level/form management | P1 |
| `admin.academic.sessions` | `SessionIndex` | `admin/pages/session.php` | L/C | Session CRUD parity | P1 |
| `admin.academic.timetable` | `TimetableIndex` | `admin/pages/academic/timetable.php` | L/C | Slot edit / add | P1 |

---

## Admin — settings (`/admin/settings`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.settings.roles` | `SettingsUserRolesPage` | `admin/pages/settings/roles.php` | L/C | — | P2 |
| `admin.settings.image-validation` | `ImageValidationPage` | `admin/pages/settings/image-validation.php` | C | — | P3 |
| `admin.settings.school` | `SchoolProfileForm` | `admin/setup/school.php` | C | — | P3 |
| `admin.settings.users` | `UsersIndexPage` | `admin/pages/settings/users.php` | L/C/M | User add/modal parity | P1 |
| `admin.settings.backup` | `BackupIndex` | `admin/pages/settings/backup.php` | L/C | Run backup action if missing | P2 |
| `admin.settings.backup.download` | `BackupDownloadController` | *(download handler in legacy backup UI)* | — | — | P3 |
| `admin.settings.licence` | `LicenceSettingsPage` | `admin/pages/settings/licence.php` | C | — | P3 |

---

## Admin — grading (`/admin/grading`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.grading.points` | `GradePointsPage` | `admin/pages/grading/points.php` | L/C | Point row add/edit | P2 |
| `admin.grading.enter` | `EnterGradesPage` | `admin/pages/grading/enter.php` | L/C/M | Cell edit, bulk | P1 |
| `admin.grading.upload` | `UploadGradesPage` | `admin/pages/grading/upload.php` | C | File upload parity | P1 |
| `admin.grading.transcripts` | `PrintRecordController` | `admin/pages/grading/transcripts.php` | M | — | P2 |
| `admin.grading.approve` | `ApproveGradesPage` | `admin/pages/grading/approve.php` | L/C | Approve actions | P1 |

---

## Admin — finance (`/admin/finance`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.finance.fees` | `FeesIndex` | `admin/pages/finance/fees.php` | L/C | Fee structure add/edit | P1 |
| `admin.finance.payments` | `FinancePaymentsPage` | `admin/pages/finance/payments.php` | L/C/M | Record payment modals | P1 |
| `admin.finance.outstanding` | `FinanceOutstandingPage` | `admin/pages/finance/outstanding.php` | L | — | P2 |
| `admin.finance.scholarships` | `ScholarshipsIndexPage` | `admin/pages/finance/scholarships.php` | L/C | Award / edit | P2 |

---

## Admin — reports (`/admin/reports`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `admin.reports.academic` | `AcademicReportPage` | `admin/pages/reports/academic.php` | L | Export/filters | P2 |
| `admin.reports.payments` | `PaymentReportPage` | `admin/pages/reports/payments.php` | L | Export/filters | P2 |
| `admin.reports.attendance` | `AttendanceReportPage` | `admin/pages/reports/attendance.php` | L | Export/filters | P2 |

---

## Admin — tools (`/tools`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `tools.passport-validator` | `PassportValidatorPage` | `tools/test_passport_validation.php` | C | — | P3 |

---

## Student (`/student`, `/student-setup`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `student.setup.personal` | `StudentSetupPersonalPage` | `student/setup/personal.php` | C | — | P2 |
| `student.setup.status` | `StudentSetupStatusPage` | `student/setup/activate.php` | C | — | P2 |
| `student.setup.guardian` | `StudentSetupGuardianPage` | `student/setup/guardian.php` | C | Add guardian flow | P2 |
| `student.setup.delete` | `StudentSetupDeletePage` | `student/setup/delete-account.php` | C | — | P3 |
| `student.dashboard` | `StudentDashboardPage` | `student/dashboard.php` | L | — | P3 |
| `student.evaluation.perform` | `StudentEvaluationPerformPage` | `student/pages/perform-evaluation.php` | C | — | P2 |
| `student.evaluation` | `StudentEvaluationPage` | `student/pages/evaluation.php` | L/M | — | P2 |
| `student.fees.allowance` | `StudentFeesAllowancePage` | `student/pages/fees/allowance.php` | C | — | P2 |
| `student.fees.index` | `StudentFeesIndexPage` | `student/pages/fees/index.php` | L | — | P3 |
| `student.fees.history` | `StudentPaymentHistoryPage` | `student/pages/fees/history.php` | L | — | P3 |
| `student.profile` | `StudentProfilePage` | `student/profile.php` | L/C/M | Edit/modal parity | P1 |
| `student.courses` | `StudentCoursesPage` | `student/pages/courses.php` | L | — | P3 |
| `student.timetable` | `StudentTimetablePage` | `student/pages/timetable.php` | L | — | P3 |
| `student.results` | `StudentResultsPage` | `student/pages/results.php` | L | — | P3 |
| `student.transcript` | `StudentTranscriptPage` | `student/pages/transcript.php` | M | — | P3 |
| `student.attendance` | `StudentAttendancePage` | `student/pages/attendance.php` | L | — | P3 |
| `student.job-alerts` | `StudentJobAlertsPage` | `student/pages/job-alerts.php` | L | — | P3 |
| `student.clearance` | `StudentClearancePage` | `student/pages/clearance.php` | L/C | Unit updates vs legacy | P2 |
| `student.medical` | `StudentMedicalPage` | `student/pages/medical.php` | L | Read-only vs legacy edit | P2 |
| `student.discipline` | `StudentDisciplinePage` | `student/pages/discipline.php` | L | — | P3 |

---

## Teacher (`/teacher`, `/teacher/setup`)

| Route name | Laravel surface | Legacy file | L/C/M | Laravel gap / notes | Priority |
|------------|-----------------|-------------|-------|---------------------|----------|
| `teacher.setup` | `TeacherSetupWizard` | `teacher/setup-personal.php` | C | Wizard vs single page | P2 |
| `teacher.dashboard` | `TeacherDashboardPage` | `teacher/dashboard.php` | L | — | P3 |
| `teacher.profile` | Blade `setup.teacher-personal` | `teacher/setup-personal.php` | C | Livewire parity optional | P3 |
| `teacher.courses` | `TeacherCoursesPage` | `teacher/pages/courses.php` | L | — | P3 |
| `teacher.courses.materials` | `TeacherCourseMaterialsPage` | `teacher/pages/materials.php` | L/C | Upload/add | P1 |
| `teacher.timetable` | `TeacherTimetablePage` | `teacher/pages/timetable.php` | L | — | P3 |
| `teacher.students` | `TeacherStudentsPage` | `teacher/pages/students.php` | L/M | Class list / detail | P2 |
| `teacher.attendance` | `TeacherAttendancePage` | `teacher/pages/attendance.php` | L/C | Mark attendance | P1 |
| `teacher.performance` | `TeacherPerformancePage` | `teacher/pages/performance.php` | L/C | — | P2 |
| `teacher.results.upload` | `TeacherResultsUploadPage` | `teacher/pages/results-upload.php` | C | Upload parity | P1 |
| `teacher.grades` | `TeacherGradesPage` | `teacher/pages/grades.php` | L/C/M | Enter/edit grades | P1 |
| `teacher.announcements` | `TeacherAnnouncementsPage` | `teacher/pages/announcements.php` | L/C | Post | P2 |
| `teacher.messages` | `TeacherMessagesPage` | `teacher/pages/messages.php` | L/C/M | Thread UI | P2 |

---

## Students admin — summary (this milestone)

| Topic | Status |
|-------|--------|
| All students — Import | Disabled **Import Students** button (coming soon); no CSV import yet. |
| All students — legacy modal / export | Still **out of scope**; see `admin.students.index` row above. |
| Promotion | **Done** in Livewire: session gate, `students.promotion_mode`, auto vs manual UI, manual preview/confirm, history. |
| Graduation | **Done**: process form, stats, program filter on list. **Follow-up**: admin clearance console (student portal clearance exists). |
| Medical | **Done**: admin search → edit → save. |
| Discipline | **Done**: add, filters, close case, expanded columns. |

---

## UI conventions (for future work)

- Prefer **`x-modal`** ([`resources/views/components/modal.blade.php`](../resources/views/components/modal.blade.php)) or Alpine `open-modal` for destructive/short confirms; use **inline panels** for large forms (matches legacy admin students pages).
- Primary actions live in **card headers** (e.g. **Add …**, **Import …** disabled until ready).
- Use **`CollegeFlash::forNextRequestToo`** ([`App\Support\CollegeFlash`](../app/Support/CollegeFlash.php)) for success messages consistent with other admin Livewire pages.
