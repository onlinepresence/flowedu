<?php

use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Admin\PrintRecordController;
use App\Livewire\Admin\Academic\DepartmentIndex;
use App\Livewire\Admin\Academic\FacultyIndex;
use App\Livewire\Admin\Academic\ProgramClassesPage;
use App\Livewire\Admin\Academic\ProgramIndex;
use App\Livewire\Admin\Academic\ProgramManagePage;
use App\Livewire\Admin\Academic\SessionIndex;
use App\Livewire\Admin\Academic\TimetableIndex;
use App\Livewire\Admin\AdminDashboardPage;
use App\Livewire\Admin\Finance\FeesIndex;
use App\Livewire\Admin\Finance\FinanceOutstandingPage;
use App\Livewire\Admin\Finance\FinancePaymentsPage;
use App\Livewire\Admin\Finance\ScholarshipsIndexPage;
use App\Livewire\Admin\Finance\AllowancesIndexPage;
use App\Livewire\Admin\Grading\ApproveGradesPage;
use App\Livewire\Admin\Grading\EnterGradesPage;
use App\Livewire\Admin\Grading\GradePointsPage;
use App\Livewire\Admin\Grading\TranscriptIndexPage;
use App\Livewire\Admin\Grading\UploadGradesPage;
use App\Livewire\Admin\Reports\AcademicReportPage;
use App\Livewire\Admin\Reports\AttendanceReportPage;
use App\Livewire\Admin\Reports\PaymentReportPage;
use App\Livewire\Admin\Reports\EnrollmentReportPage;
use App\Livewire\Admin\Reports\WelfareReportPage;
use App\Livewire\Admin\Settings\BackupIndex;
use App\Livewire\Admin\Settings\ImageValidationPage;
use App\Livewire\Admin\Settings\LicenceSettingsPage;
use App\Livewire\Admin\Settings\SchoolProfileForm;
use App\Livewire\Admin\Settings\SettingsUserRolesPage;
use App\Livewire\Admin\Settings\UsersIndexPage;
use App\Livewire\Admin\Settings\SystemPreferencesPage;
use App\Livewire\Admin\Memos\MemoIndexPage;
use App\Livewire\Admin\Memos\MemoDetailPage;
use App\Livewire\Admin\Setup\AdminSetupPersonalPage;
use App\Livewire\Admin\Setup\SetupActivatePage;
use App\Livewire\Admin\Setup\SetupDepartmentPage;
use App\Livewire\Admin\Setup\SetupHallPage;
use App\Livewire\Admin\Setup\SetupLicenceForm;
use App\Livewire\Admin\Setup\SetupProgramPage;
use App\Livewire\Admin\Staff\AnnouncementsStaffPage;
use App\Livewire\Admin\Staff\AdministratorsListPage;
use App\Livewire\Admin\Staff\CourseMaterialsPage;
use App\Livewire\Admin\Staff\EvaluationIndexPage;
use App\Livewire\Admin\Staff\EvaluationManagePage;
use App\Livewire\Admin\Staff\EvaluationPreviewPage;
use App\Livewire\Admin\Staff\InstitutionRolesPage;
use App\Livewire\Admin\Staff\NonTeachingListPage;
use App\Livewire\Admin\Staff\StaffAssignmentsListPage;
use App\Livewire\Admin\Staff\StaffHomePage;
use App\Livewire\Admin\Staff\TeacherAssignmentsPage;
use App\Livewire\Admin\Staff\TeacherListPage;
use App\Livewire\Admin\Staff\TeacherRoleListPage;
use App\Livewire\Admin\Students\ApproveStudentPage;
use App\Livewire\Admin\Students\DisciplineRecordsPage;
use App\Livewire\Admin\Students\GraduationIndexPage;
use App\Livewire\Admin\Students\MedicalRecordsPage;
use App\Livewire\Admin\Students\PromotionIndexPage;
use App\Livewire\Admin\Students\StudentIndex;
use App\Livewire\Admin\Students\StudentShowPage;
use App\Livewire\Admin\Students\JobsIndexPage;
use App\Livewire\Admin\Tools\PassportValidatorPage;
use Illuminate\Support\Facades\Route;

$adminSetup = ['auth', 'college.user-type:admin,staff'];

$adminApp = [
    'auth',
    'college.user-type:admin,staff',
    'college.valid-admin',
    'college.school-ready',
];

Route::middleware($adminSetup)->prefix('admin-setup')->group(function () {
    Route::get('personal', AdminSetupPersonalPage::class)->name('admin.setup.personal');
    Route::get('school', SchoolProfileForm::class)->name('admin.setup.school');
    Route::get('licence', SetupLicenceForm::class)->name('admin.setup.licence');
    Route::get('programs', SetupProgramPage::class)
        ->middleware('college.departments-exist')
        ->name('admin.setup.programs');
    Route::get('halls', SetupHallPage::class)->name('admin.setup.halls');
    Route::get('departments', SetupDepartmentPage::class)->name('admin.setup.departments');
    Route::get('faculties', FacultyIndex::class)->name('admin.setup.faculties');
    Route::get('activate', SetupActivatePage::class)->name('admin.setup.activate');
});

Route::middleware($adminApp)->prefix('admin')->group(function () {
    Route::get('impersonation', function () {
        abort_unless(auth()->user()?->canStartImpersonation() ?? false, 403);

        return redirect()->route('admin.settings.users');
    })->middleware('college.licence:impersonation')->name('admin.impersonation.index');
    Route::get('dashboard', AdminDashboardPage::class)->name('admin.dashboard');
    Route::get('approve-student/{index_number}/{guardian}/{id}', ApproveStudentPage::class)->name('admin.approve-student');
    Route::get('students/print/{index_number}', [PrintRecordController::class, 'student'])->name('admin.students.print');
    Route::get('students', StudentIndex::class)->name('admin.students.index');
    Route::get('students/jobs', JobsIndexPage::class)->name('admin.students.jobs');
    Route::get('students/{index_number}/view', StudentShowPage::class)->name('admin.students.show');
    Route::get('profile', AdminSetupPersonalPage::class)->name('admin.profile');
});

Route::middleware($adminApp)->prefix('admin/staff')->group(function () {
    Route::middleware(['college.licence:evaluations'])->group(function () {
        Route::get('evaluations', EvaluationIndexPage::class)->name('admin.evaluations');
        Route::get('evaluation/demo/{form_code}', EvaluationPreviewPage::class)->name('admin.evaluation.preview');
        Route::get('evaluation/{form_code}/{tab?}', EvaluationManagePage::class)->name('admin.evaluation');
    });

    Route::middleware(['college.licence:staff_hr'])->group(function () {
        Route::get('/', StaffHomePage::class)->name('admin.staff.index');
        Route::get('teachers', TeacherListPage::class)->name('admin.staff.teachers');
        Route::get('teachers/import-template', [App\Http\Controllers\Admin\TeacherImportTemplateController::class, 'download'])->name('admin.staff.teachers.import-template');
        Route::get('administrators', AdministratorsListPage::class)->name('admin.staff.administrators');
        Route::get('non-teaching', NonTeachingListPage::class)->name('admin.staff.non-teaching');
        Route::get('assignments', StaffAssignmentsListPage::class)->name('admin.staff.assignments');
        Route::get('teacher-assignments', TeacherAssignmentsPage::class)->name('admin.staff.teacher-assignments');
        Route::get('teacher-roles', TeacherRoleListPage::class)->name('admin.staff.teacher-roles');
        Route::get('staff-assignments', StaffAssignmentsListPage::class)->name('admin.staff.staff-assignments');
        Route::get('roles', InstitutionRolesPage::class)->name('admin.staff.roles');
        Route::get('materials', CourseMaterialsPage::class)->name('admin.staff.materials');
        Route::get('announcements', AnnouncementsStaffPage::class)->name('admin.staff.announcements');
    });
});

Route::middleware($adminApp)->prefix('admin/academic')->group(function () {
    Route::get('faculty', FacultyIndex::class)->name('admin.academic.faculty');
    Route::get('department', DepartmentIndex::class)->name('admin.academic.department');
    Route::get('program', ProgramIndex::class)->name('admin.academic.program');
    Route::get('program/{program_id}/{form_level}', ProgramManagePage::class)->name('program.manage');
    Route::get('program/{program_id}', ProgramClassesPage::class)->name('program.classes');
    Route::get('sessions', SessionIndex::class)->name('admin.academic.sessions');
    Route::get('timetable', TimetableIndex::class)
        ->middleware('college.licence:timetable')
        ->name('admin.academic.timetable');
});

Route::middleware([...$adminApp, 'college.licence:progression'])->prefix('admin/students')->group(function () {
    Route::get('promotion', PromotionIndexPage::class)->name('admin.students.promotion');
    Route::get('graduation', GraduationIndexPage::class)->name('admin.students.graduation');
});

Route::middleware([...$adminApp, 'college.licence:student_welfare'])->prefix('admin/students')->group(function () {
    Route::get('medical', MedicalRecordsPage::class)->name('admin.students.medical');
    Route::get('discipline', DisciplineRecordsPage::class)->name('admin.students.discipline');
});

Route::middleware($adminApp)->prefix('admin/settings')->group(function () {
    Route::get('roles', SettingsUserRolesPage::class)
        ->middleware('college.licence:system_admin')
        ->name('admin.settings.roles');
    Route::get('image-validation', ImageValidationPage::class)
        ->middleware('college.licence:system_admin')
        ->name('admin.settings.image-validation');
    Route::get('school', SchoolProfileForm::class)->name('admin.settings.school');
    Route::get('users', UsersIndexPage::class)
        ->middleware('college.licence:system_admin')
        ->name('admin.settings.users');
    Route::get('backup', BackupIndex::class)
        ->middleware('college.licence:system_admin')
        ->name('admin.settings.backup');
    Route::get('backup/{backup}/download', [BackupDownloadController::class, 'show'])
        ->middleware('college.licence:system_admin')
        ->name('admin.settings.backup.download');
    Route::get('system-preferences', SystemPreferencesPage::class)
        ->middleware('college.licence:system_admin')
        ->name('admin.settings.system-preferences');
    Route::get('licence', LicenceSettingsPage::class)->name('admin.settings.licence');
});

Route::middleware($adminApp)->prefix('admin/grading')->group(function () {
    Route::get('points', GradePointsPage::class)->name('admin.grading.points');
    Route::get('enter', EnterGradesPage::class)->name('admin.grading.enter');
    Route::get('upload', UploadGradesPage::class)->name('admin.grading.upload');
    Route::get('transcripts', TranscriptIndexPage::class)->name('admin.grading.transcripts.index');
    Route::get('transcripts/{index_number}', [PrintRecordController::class, 'transcript'])->name('admin.grading.transcripts');
    Route::get('approve', ApproveGradesPage::class)->name('admin.grading.approve');
});

Route::middleware([...$adminApp, 'college.licence:finance'])->prefix('admin/finance')->group(function () {
    Route::get('fees', FeesIndex::class)->name('admin.finance.fees');
    Route::get('payments', FinancePaymentsPage::class)->name('admin.finance.payments');
    Route::get('outstanding', FinanceOutstandingPage::class)->name('admin.finance.outstanding');
    Route::get('scholarships', ScholarshipsIndexPage::class)->name('admin.finance.scholarships');
    Route::get('allowances', AllowancesIndexPage::class)->name('admin.finance.allowances');
});

Route::middleware([...$adminApp, 'college.licence:reports'])->prefix('admin/reports')->group(function () {
    Route::get('academic', AcademicReportPage::class)->name('admin.reports.academic');
    Route::get('payments', PaymentReportPage::class)->name('admin.reports.payments');
    Route::get('attendance', AttendanceReportPage::class)->name('admin.reports.attendance');
    Route::get('enrollment', EnrollmentReportPage::class)->name('admin.reports.enrollment');
    Route::get('welfare', WelfareReportPage::class)->name('admin.reports.welfare');
});

Route::middleware([...$adminApp, 'college.licence:system_admin'])->prefix('tools')->group(function () {
    Route::get('passport-validator', PassportValidatorPage::class)->name('tools.passport-validator');
});

Route::middleware([...$adminApp, 'college.licence:memos'])->prefix('admin/memos')->group(function () {
    Route::get('/', MemoIndexPage::class)->name('admin.memos.index');
    Route::get('{memo}', MemoDetailPage::class)->name('admin.memos.show');
});

Route::middleware([...$adminApp, 'college.licence:practicum'])->prefix('admin/practicum')->group(function () {
    Route::get('assign', \App\Livewire\Admin\Practicum\AdminPracticumAssignPage::class)->name('admin.practicum.assign');
    Route::get('reports', \App\Livewire\Admin\Practicum\AdminPracticumReportPage::class)->name('admin.practicum.reports');
});
