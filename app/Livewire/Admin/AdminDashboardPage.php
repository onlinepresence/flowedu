<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Services\SchoolLicenceService;
use App\Services\StudentLicenceCapService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminDashboardPage extends Component
{
    public function render(
        SchoolLicenceService $licenceService,
        StudentLicenceCapService $capService,
    ): View {
        $pendingCount = Student::query()->where('approved', false)->count();
        $approvedCount = Student::query()->where('approved', true)->count();
        $maxStudents = $licenceService->maxActiveStudents();
        $activeForCap = $capService->activeStudentsCount();
        $capNotice = $capService->dashboardCapNotice();
        $capNoticeIsBlock = $capNotice !== null && $licenceService->studentCapMode() === 'block';

        $pendingPreview = Student::query()
            ->where('approved', false)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(5)
            ->get(['id', 'index_number', 'firstname', 'othernames', 'lastname']);

        return view('livewire.admin.admin-dashboard-page', [
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'maxStudents' => $maxStudents,
            'activeForCap' => $activeForCap,
            'capNotice' => $capNotice,
            'capNoticeIsBlock' => $capNoticeIsBlock,
            'pendingPreview' => $pendingPreview,
            'canFinance' => $licenceService->can('finance'),
        ])->layout('components.layouts.admin', [
            'title' => __('Dashboard'),
            'headerDescription' => __('Welcome back! Here is an overview of the school status, quick actions, and recent activities.'),
        ]);
    }
}
