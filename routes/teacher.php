<?php

use App\Http\Controllers\Teacher\TeacherAttendanceSheetDownloadController;
use App\Http\Controllers\Teacher\TeacherAttendanceSheetPreviewController;
use App\Http\Controllers\Teacher\TeacherCourseMaterialDownloadController;
use App\Http\Controllers\Teacher\TeacherUploadController;
use App\Livewire\Teacher\TeacherAnnouncementsPage;
use App\Livewire\Teacher\TeacherAttendancePage;
use App\Livewire\Teacher\TeacherCourseMaterialsPage;
use App\Livewire\Teacher\TeacherCoursesPage;
use App\Livewire\Teacher\TeacherDashboardPage;
use App\Livewire\Teacher\TeacherGradesPage;
use App\Livewire\Teacher\TeacherMessagesPage;
use App\Livewire\Teacher\TeacherPerformancePage;
use App\Livewire\Teacher\TeacherProfilePage;
use App\Livewire\Teacher\TeacherResultsUploadPage;
use App\Livewire\Teacher\TeacherSetupWizard;
use App\Livewire\Teacher\TeacherStudentsPage;
use App\Livewire\Teacher\TeacherTimetablePage;
use Illuminate\Support\Facades\Route;

$teacherSetup = [
    'auth',
    'college.user-type:teacher',
    'college.teacher-setup-gate',
    'college.school-ready',
];

$teacherApp = [
    'auth',
    'college.user-type:teacher',
    'college.valid-teacher',
    'college.school-ready',
];

Route::middleware($teacherSetup)->prefix('teacher/setup')->group(function () {
    Route::get('/', TeacherSetupWizard::class)->name('teacher.setup');
});

Route::middleware($teacherApp)->prefix('teacher')->group(function () {
    Route::get('dashboard', TeacherDashboardPage::class)->name('teacher.dashboard');
    Route::get('account/profile-photo', [TeacherUploadController::class, 'profilePhoto'])->name('teacher.profile.photo');
    Route::get('account/document/{type}', [TeacherUploadController::class, 'document'])
        ->where('type', 'cv|certificate|id_document')
        ->name('teacher.profile.document');
    Route::get('profile', TeacherProfilePage::class)->name('teacher.profile');

    // Courses permissions
    Route::middleware(['college.teacher-permission:courses'])->group(function () {
        Route::get('courses', TeacherCoursesPage::class)->name('teacher.courses');
        Route::get('courses/materials', TeacherCourseMaterialsPage::class)->name('teacher.courses.materials');
        Route::get('courses/materials/{material}/download', TeacherCourseMaterialDownloadController::class)
            ->name('teacher.courses.materials.download');
        Route::get('timetable', TeacherTimetablePage::class)->name('teacher.timetable');
    });

    // Students permissions
    Route::middleware(['college.teacher-permission:students'])->group(function () {
        Route::get('students', TeacherStudentsPage::class)->name('teacher.students');
        Route::get('attendance', TeacherAttendancePage::class)->name('teacher.attendance');
        Route::get('attendance/preview-sheet', TeacherAttendanceSheetPreviewController::class)->name('teacher.attendance.preview');
        Route::get('attendance/sheets/{sheet}/download', TeacherAttendanceSheetDownloadController::class)->name('teacher.attendance.sheets.download');
        Route::get('performance', TeacherPerformancePage::class)->name('teacher.performance');
        Route::get('practicum', \App\Livewire\Teacher\TeacherPracticumPage::class)->name('teacher.practicum');
    });

    // Assessments permissions
    Route::middleware(['college.teacher-permission:assessments'])->group(function () {
        Route::get('results/enter', \App\Livewire\Admin\Grading\EnterGradesPage::class)->name('teacher.results.enter');
        Route::get('results/upload', \App\Livewire\Admin\Grading\UploadGradesPage::class)->name('teacher.results.upload');
        Route::get('grades', TeacherGradesPage::class)->name('teacher.grades');
    });

    // Communication permissions
    Route::middleware(['college.teacher-permission:communication'])->group(function () {
        Route::get('announcements', TeacherAnnouncementsPage::class)->name('teacher.announcements');
        Route::get('messages', TeacherMessagesPage::class)->name('teacher.messages');
        Route::get('memos', \App\Livewire\Teacher\TeacherMemosPage::class)->name('teacher.memos.index');
        Route::get('memos/{memo}', \App\Livewire\Teacher\TeacherMemoDetailPage::class)->name('teacher.memos.show');
        Route::get('lesson-plans', \App\Livewire\Teacher\TeacherLessonPlansPage::class)->name('teacher.lesson-plans');
    });
});
