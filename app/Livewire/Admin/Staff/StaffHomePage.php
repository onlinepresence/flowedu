<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Models\Admin;
use App\Models\Announcement;
use App\Models\CourseMaterial;
use App\Models\NonTeachingStaff;
use App\Models\StaffAssignment;
use App\Models\StaffRole;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherRole;
use App\Models\UserRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StaffHomePage extends Component
{
    public bool $showAddStaffModal = false;

    public function openAddStaffModal(): void
    {
        $this->showAddStaffModal = true;
    }

    public function closeAddStaffModal(): void
    {
        $this->showAddStaffModal = false;
    }

    public function render(): View
    {
        return view('livewire.admin.staff.staff-home-page', [
            'adminCount' => Admin::query()->count(),
            'teacherCount' => Teacher::query()->count(),
            'nonTeachingCount' => NonTeachingStaff::query()->count(),
            'staffAssignmentCount' => StaffAssignment::query()->count(),
            'teacherAssignmentCount' => TeacherAssignment::query()->count(),
            'teacherRoleCount' => TeacherRole::query()->count(),
            'userRoleCount' => UserRole::query()->count(),
            'materialCount' => CourseMaterial::query()->count(),
            'announcementCount' => Announcement::query()->count(),
        ])->layout('components.layouts.admin', [
            'title' => __('Staff Overview'),
            'headerTitle' => __('Administrative Overview'),
            'headerDescription' => __('Central hub for managing administrators, teaching, and non-teaching staff assignments.'),
        ]);
    }
}
