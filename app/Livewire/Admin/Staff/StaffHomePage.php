<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Models\Admin;
use App\Models\Announcement;
use App\Models\CourseMaterial;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherRole;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class StaffHomePage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = 'all';

    public string $filterDepartment = 'all';

    public string $filterStatus = 'all';

    public bool $showAddStaffModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
        'filterDepartment' => ['except' => 'all'],
        'filterStatus' => ['except' => 'all'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

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
        $query = User::query()->whereIn('type', ['admin', 'teacher'])->with(['admin.department', 'teacher.department', 'admin.role']);

        if ($this->search !== '') {
            $q = '%' . $this->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhere('username', 'like', $q)
                    ->orWhereHas('admin', function ($a) use ($q) {
                        $a->where('lastname', 'like', $q)
                          ->orWhere('othernames', 'like', $q)
                          ->orWhere('phone_number', 'like', $q);
                    })
                    ->orWhereHas('teacher', function ($t) use ($q) {
                        $t->where('lastname', 'like', $q)
                          ->orWhere('othernames', 'like', $q)
                          ->orWhere('phone_number', 'like', $q);
                    });
            });
        }

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterDepartment !== 'all') {
            $deptId = (int) $this->filterDepartment;
            $query->where(function ($sub) use ($deptId) {
                $sub->whereHas('admin', function ($a) use ($deptId) {
                    $a->where('department_id', $deptId);
                })->orWhereHas('teacher', function ($t) use ($deptId) {
                    $t->where('department_id', $deptId);
                });
            });
        }

        if ($this->filterStatus !== 'all') {
            $isActive = $this->filterStatus === 'active';
            $query->where('active', $isActive);
        }

        $staff = $query->paginate(15);
        $departments = Department::query()->orderBy('name')->get();

        return view('livewire.admin.staff.staff-home-page', [
            'staff' => $staff,
            'departments' => $departments,
            'adminCount' => Admin::query()->count(),
            'teacherCount' => Teacher::query()->count(),
            'teacherAssignmentCount' => TeacherAssignment::query()->count(),
            'teacherRoleCount' => TeacherRole::query()->count(),
            'userRoleCount' => UserRole::query()->count(),
            'materialCount' => CourseMaterial::query()->count(),
            'announcementCount' => Announcement::query()->count(),
        ])->layout('components.layouts.admin', [
            'title' => __('Staff Directory'),
            'headerTitle' => __('Staff Directory'),
            'headerDescription' => __('Central portal for searching and filtering all teaching and administrative staff members.'),
        ]);
    }
}
