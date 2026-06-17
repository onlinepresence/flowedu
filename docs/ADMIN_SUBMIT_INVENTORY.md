# `admin/submit.php` inventory (`submit` request parameter)

Auto-extracted branch keys from [admin/submit.php](../../admin/submit.php). Map each to a Laravel **Form Request + Action** and/or **Livewire** handler when porting.

| `submit` value | Notes / legacy area |
|----------------|---------------------|
| `create_admin`, `update_admin` | Admin profile / onboarding |
| `add_user` | Settings: create user accounts |
| `setup_school` | School record create/update + logo |
| `setup_school_licence` | Initial package tier (setup only) |
| `update_school_licence` | Owner-only licence row |
| `create_hall` | Halls |
| `change_school_status` | School ready / admit flags |
| `fetch_user` | AJAX user lookup |
| `admin_update_student`, `admin_save_guardian` | Student admin edit |
| `create_faculty`, `update_faculty` | Faculties |
| `create_department`, `update_department` | Departments |
| `create_program`, `update_program` | Programs |
| `create_course`, `update_course` | Courses |
| `create_role`, `update_role` | `user_roles` |
| `create_evaluation_form`, `update_evaluation_form` | Evaluations |
| `create_evaluation_question`, `update_evaluation_question` | Evaluations |
| `student_promotion`, `preview_promotion`, `confirm_promotion` | Promotions |
| `process_graduation` | Graduation |
| `update_medical`, `add_disciplinary_record` | Student professional |
| `save_promotion_settings`, `save_clearance_department` | Settings |
| `create_timetable`, `save_timetable`, `add_timetable_class`, `update_timetable_class` | Timetable |
| `update_grade_points` | Grading |
| `enter_results`, `upload_results` | Results |
| `generate_transcript`, `bulk_generate_transcripts` | Transcripts |
| `assign_teacher`, `assign_teacher_role` | Staff |
| `assign_staff`, `assign_staff_role` | Staff |
| `add_non_teaching_staff` | Staff |
| `update_fee_structure`, `add_scholarship` | Finance (tables may be deferred — see §5 table audit) |
| `backup_database`, `restore_database` | Backup |
| `approve_material`, `reject_material`, `delete_material` | Course materials |
| `approve_announcement`, `reject_announcement`, `archive_announcement`, `delete_announcement` | Announcements |
| `approve_results`, `reject_results` | Results approval |
| `update_image_validation_settings` | Settings |
| `delete-item` | Generic delete |

Also handle `response_type=json` / AJAX siblings under `admin/ajax/` separately (plan §9.3). See [AJAX_INVENTORY.md](AJAX_INVENTORY.md), [TEACHER_SUBMIT_INVENTORY.md](TEACHER_SUBMIT_INVENTORY.md), and [PHASE_CDE_MINING_LOOP.md](PHASE_CDE_MINING_LOOP.md).

## Mapped — admin staff (Livewire Section C)

| `submit` value | Laravel surface |
|----------------|-----------------|
| `add_non_teaching_staff` | [`NonTeachingListPage`](../app/Livewire/Admin/Staff/NonTeachingListPage.php) + [`CreateNonTeachingStaffUser`](../app/Actions/Staff/CreateNonTeachingStaffUser.php) |
| `assign_staff` | [`StaffAssignmentsListPage`](../app/Livewire/Admin/Staff/StaffAssignmentsListPage.php) |
| `assign_staff_role` | [`StaffRoleListPage`](../app/Livewire/Admin/Staff/StaffRoleListPage.php) |
| `assign_teacher` | [`TeacherListPage`](../app/Livewire/Admin/Staff/TeacherListPage.php) + [`CreateTeacherUser`](../app/Actions/Staff/CreateTeacherUser.php) |
| Teacher bulk (spreadsheet) | [`TeacherListPage`](../app/Livewire/Admin/Staff/TeacherListPage.php) `runImport()` + [`TeacherSpreadsheetImportService`](../app/Services/TeacherSpreadsheetImportService.php) + Filepond `purpose=teacher_import` |
| `assign_teacher_role` | [`TeacherRoleListPage`](../app/Livewire/Admin/Staff/TeacherRoleListPage.php) |
| Teacher–course assignment | [`TeacherAssignmentsPage`](../app/Livewire/Admin/Staff/TeacherAssignmentsPage.php) |
| Evaluation form delete | [`EvaluationIndexPage`](../app/Livewire/Admin/Staff/EvaluationIndexPage.php) `confirmDeleteForm()` — only when **inactive** and **no responses** |

## Mapped — admin students (Livewire, no parallel JSON)

| `submit` value | Laravel surface |
|----------------|-----------------|
| `save_promotion_settings` | [`App\Livewire\Admin\Students\PromotionIndexPage`](../app/Livewire/Admin/Students/PromotionIndexPage.php) `savePromotionMode()` → `settings` row `students.promotion_mode` |
| `preview_promotion` / `confirm_promotion` | [`PromotionIndexPage`](../app/Livewire/Admin/Students/PromotionIndexPage.php) `previewPromotion()` / `confirmPromotion()` + [`App\Services\Students\ManualPromotionService`](../app/Services/Students/ManualPromotionService.php) |
| `process_graduation` | [`App\Livewire\Admin\Students\GraduationIndexPage`](../app/Livewire/Admin/Students/GraduationIndexPage.php) `processGraduation()` + [`App\Services\Students\ProcessGraduationService`](../app/Services/Students/ProcessGraduationService.php) |
| `update_medical` | [`MedicalRecordsPage`](../app/Livewire/Admin/Students/MedicalRecordsPage.php) `saveMedical()` |
| `add_disciplinary_record` | [`DisciplineRecordsPage`](../app/Livewire/Admin/Students/DisciplineRecordsPage.php) `addRecord()` |
| `save_clearance_department` | **Deferred** — admin bulk clearance; student portal [`StudentClearancePage`](../app/Livewire/Student/StudentClearancePage.php) only |
