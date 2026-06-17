<?php

use App\Livewire\Student\StudentAttendancePage;
use App\Livewire\Student\StudentClearancePage;
use App\Livewire\Student\StudentCoursesPage;
use App\Livewire\Student\StudentDashboardPage;
use App\Livewire\Student\StudentDisciplinePage;
use App\Livewire\Student\StudentEvaluationPage;
use App\Livewire\Student\StudentEvaluationPerformPage;
use App\Livewire\Student\StudentFeesAllowancePage;
use App\Livewire\Student\StudentFeesIndexPage;
use App\Livewire\Student\StudentJobAlertsPage;
use App\Livewire\Student\StudentMedicalPage;
use App\Livewire\Student\StudentPaymentHistoryPage;
use App\Livewire\Student\StudentProfilePage;
use App\Livewire\Student\StudentResultsPage;
use App\Livewire\Student\StudentScholarshipsPage;
use App\Livewire\Student\StudentSetupDeletePage;
use App\Livewire\Student\StudentSetupGuardianPage;
use App\Livewire\Student\StudentSetupPersonalPage;
use App\Livewire\Student\StudentSetupStatusPage;
use App\Livewire\Student\StudentTimetablePage;
use App\Livewire\Student\StudentTranscriptPage;
use Illuminate\Support\Facades\Route;

$studentSetup = [
    'auth',
    'college.user-type:student',
    'college.school-ready',
];

$studentApp = [
    'auth',
    'college.user-type:student',
    'college.school-ready',
    'college.student-ready',
];

Route::middleware($studentSetup)->prefix('student-setup')->group(function () {
    Route::get('personal', StudentSetupPersonalPage::class)->name('student.setup.personal');
    Route::get('status', StudentSetupStatusPage::class)->name('student.setup.status');
    Route::get('guardian', StudentSetupGuardianPage::class)->name('student.setup.guardian');
    Route::get('delete', StudentSetupDeletePage::class)->name('student.setup.delete');
});

Route::middleware($studentApp)->prefix('student')->group(function () {
    Route::get('dashboard', StudentDashboardPage::class)->name('student.dashboard');

    Route::middleware(['college.licence:evaluations'])->group(function () {
        Route::get('evaluation/perform/{code}', StudentEvaluationPerformPage::class)->name('student.evaluation.perform');
        Route::get('evaluation/{tab?}', StudentEvaluationPage::class)->name('student.evaluation');
    });

    Route::middleware(['college.licence:finance'])->group(function () {
        Route::get('allowance', StudentFeesAllowancePage::class)->name('student.fees.allowance');
        Route::get('scholarships', StudentScholarshipsPage::class)->name('student.scholarships');
        Route::get('fees', StudentFeesIndexPage::class)->name('student.fees.index');
        Route::get('payment-history', StudentPaymentHistoryPage::class)->name('student.fees.history');
    });

    Route::get('profile', StudentProfilePage::class)->name('student.profile');
    Route::get('courses', StudentCoursesPage::class)->name('student.courses');
    Route::get('timetable', StudentTimetablePage::class)
        ->middleware('college.licence:timetable')
        ->name('student.timetable');
    Route::get('results', StudentResultsPage::class)->name('student.results');
    Route::get('transcript', StudentTranscriptPage::class)->name('student.transcript');
    Route::get('practicum', \App\Livewire\Student\StudentPracticumPage::class)
        ->middleware('college.licence:practicum')
        ->name('student.practicum');
    Route::get('attendance', StudentAttendancePage::class)
        ->middleware('college.licence:attendance')
        ->name('student.attendance');
    Route::get('job-alerts', StudentJobAlertsPage::class)->name('student.job-alerts');
    Route::get('memos', \App\Livewire\Student\StudentMemosPage::class)->name('student.memos.index');
    Route::get('memos/{memo}', \App\Livewire\Student\StudentMemoDetailPage::class)->name('student.memos.show');

    Route::middleware(['college.licence:student_welfare'])->group(function () {
        Route::get('clearance', StudentClearancePage::class)->name('student.clearance');
        Route::get('medical', StudentMedicalPage::class)->name('student.medical');
        Route::get('discipline', StudentDisciplinePage::class)->name('student.discipline');
    });
});
