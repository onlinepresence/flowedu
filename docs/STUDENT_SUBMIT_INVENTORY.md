# `student/submit.php` inventory (`submit` request parameter)

| `submit` value | Notes | Laravel surface |
|----------------|--------|-----------------|
| `create_student`, `update_student` | Admission application / profile | `App\Livewire\Student\StudentSetupPersonalPage` + `StoreStudentAdmissionRequest` / `UpdateStudentAdmissionRequest` + `SaveStudentAdmissionProfileAction` |
| `save_guardian` | Parent/guardian records | `App\Livewire\Student\StudentSetupGuardianPage` + `SaveParentGuardianRequest` + `SaveParentGuardianAction` |
| `change_status` | Post-approval activation (`is_new`) | `App\Livewire\Student\StudentSetupStatusPage` + `ActivateStudentDashboardAction` + `AssignOfficialStudentIndexNumberAction` |
| `delete-account` | Self-service cancel registration | `App\Livewire\Student\StudentSetupDeletePage` + `DeleteStudentAccountRequest` + `DeleteStudentRegistrationAction` (+ password confirmation) |
| `change_picture` | Profile photo | Covered by optional upload on `UpdateStudentAdmissionRequest` / `StudentProfilePage` (same storage rules as create) |

Photos: `college_uploads` disk, path `students/profiles/` via `StoreStudentProfilePhotoAction`. Passport checks: `PassportPhotoValidationService` + `PassportPhotoFile` rule (toggle with `COLLEGE_PASSPORT_VALIDATION_ENABLED`).

Keep AJAX/JSON contracts aligned with plan §9.3 if any callers remain.
