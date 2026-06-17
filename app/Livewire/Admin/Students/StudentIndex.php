<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class StudentIndex extends Component
{
    use WithPagination, DispatchesCollegeToasts;

    public string $search = '';

    #[Url(history: true)]
    public string $approval = 'all';

    // Filters
    public string $facultyFilter = '';
    public string $departmentFilter = '';
    public string $programFilter = '';
    public string $levelFilter = '';

    // Selection
    /** @var array<int, bool> */
    public array $selectedIds = [];
    public bool $selectAll = false;

    // Editing Student
    public ?int $editingStudentId = null;
    public string $editFirstname = '';
    public string $editOthernames = '';
    public string $editLastname = '';
    public string $editGender = '';
    public string $editPhone = '';
    public string $editAddress = '';
    public string $editLevel = '';
    public string $editProgramId = '';

    public function mount(): void
    {
        if (! in_array($this->approval, ['all', 'pending', 'approved'], true)) {
            $this->approval = 'all';
        }
    }

    public function updatedFacultyFilter(): void
    {
        $this->departmentFilter = '';
        $this->programFilter = '';
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->programFilter = '';
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingApproval(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $q = trim($this->search);
            $approval = in_array($this->approval, ['pending', 'approved'], true) ? $this->approval : 'all';

            $ids = Student::query()
                ->when($approval === 'pending', fn ($query) => $query->where('approved', false))
                ->when($approval === 'approved', fn ($query) => $query->where('approved', true))
                ->when($this->facultyFilter, fn ($query) => $query->whereHas('department', fn ($d) => $d->where('faculty_id', $this->facultyFilter)))
                ->when($this->departmentFilter, fn ($query) => $query->where('department_id', $this->departmentFilter))
                ->when($this->programFilter, fn ($query) => $query->where('program_id', $this->programFilter))
                ->when($this->levelFilter, fn ($query) => $query->where('current_year', $this->levelFilter))
                ->when($q !== '', function ($query) use ($q): void {
                    $query->where(function ($inner) use ($q): void {
                        $inner->where('index_number', 'like', '%'.$q.'%')
                            ->orWhere('lastname', 'like', '%'.$q.'%')
                            ->orWhere('firstname', 'like', '%'.$q.'%');
                    });
                })
                ->pluck('id')
                ->all();

            foreach ($ids as $id) {
                $this->selectedIds[$id] = true;
            }
        } else {
            $this->selectedIds = [];
        }
    }

    public ?int $deletingStudentId = null;

    public function confirmDeleteStudent(int $id): void
    {
        $this->deletingStudentId = $id;
        $this->dispatch('open-modal', 'delete-student-confirm-modal');
    }

    public function deleteStudent(): void
    {
        if ($this->deletingStudentId === null) {
            return;
        }

        $student = Student::findOrFail($this->deletingStudentId);
        $user = $student->user;
        if ($user) {
            $user->delete(); // Cascade deletes student record too
        } else {
            $student->delete();
        }

        $this->selectedIds = array_filter($this->selectedIds, fn($k) => $k !== $this->deletingStudentId, ARRAY_FILTER_USE_KEY);
        $this->deletingStudentId = null;
        $this->collegeToast(__('Student deleted successfully.'));
    }

    public function deleteSelected(): void
    {
        $ids = array_keys(array_filter($this->selectedIds));
        if ($ids === []) {
            return;
        }

        $students = Student::whereIn('id', $ids)->get();
        foreach ($students as $student) {
            if ($student->user) {
                $student->user->delete();
            } else {
                $student->delete();
            }
        }

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->collegeToast(__('Selected students deleted successfully.'));
    }

    public function editStudent(int $id): void
    {
        $student = Student::findOrFail($id);
        $this->editingStudentId = $id;
        $this->editFirstname = $student->firstname ?? '';
        $this->editOthernames = $student->othernames ?? '';
        $this->editLastname = $student->lastname;
        $this->editGender = $student->gender;
        $this->editPhone = $student->phone_number;
        $this->editAddress = $student->contact_address;
        $this->editLevel = $student->current_year;
        $this->editProgramId = (string) $student->program_id;

        $this->dispatch('open-modal', 'edit-student-modal');
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editFirstname' => ['required', 'string', 'max:100'],
            'editOthernames' => ['nullable', 'string', 'max:100'],
            'editLastname' => ['required', 'string', 'max:100'],
            'editGender' => ['required', 'string'],
            'editPhone' => ['required', 'string', 'max:20'],
            'editAddress' => ['required', 'string', 'max:500'],
            'editLevel' => ['required', 'in:100,200,300,400'],
            'editProgramId' => ['required', 'exists:programs,id'],
        ]);

        $student = Student::findOrFail($this->editingStudentId);
        $program = Program::findOrFail((int) $this->editProgramId);

        $student->forceFill([
            'firstname' => $this->editFirstname,
            'othernames' => $this->editOthernames ?: null,
            'lastname' => $this->editLastname,
            'gender' => $this->editGender,
            'phone_number' => $this->editPhone,
            'contact_address' => $this->editAddress,
            'current_year' => $this->editLevel,
            'program_id' => $program->id,
            'department_id' => $program->department_id,
        ])->save();

        $this->dispatch('close-modal', 'edit-student-modal');
        $this->editingStudentId = null;
        $this->collegeToast(__('Student information updated.'));
    }

    public function render(): View
    {
        $q = trim($this->search);
        $approval = in_array($this->approval, ['pending', 'approved'], true) ? $this->approval : 'all';

        $students = Student::query()
            ->with(['user', 'program.department', 'department'])
            ->withCount('parentGuardians')
            ->when($approval === 'pending', fn ($query) => $query->where('approved', false))
            ->when($approval === 'approved', fn ($query) => $query->where('approved', true))
            ->when($this->facultyFilter, fn ($query) => $query->whereHas('department', fn ($d) => $d->where('faculty_id', $this->facultyFilter)))
            ->when($this->departmentFilter, fn ($query) => $query->where('department_id', $this->departmentFilter))
            ->when($this->programFilter, fn ($query) => $query->where('program_id', $this->programFilter))
            ->when($this->levelFilter, fn ($query) => $query->where('current_year', $this->levelFilter))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner
                        ->where('index_number', 'like', '%'.$q.'%')
                        ->orWhere('admission_index', 'like', '%'.$q.'%')
                        ->orWhere('lastname', 'like', '%'.$q.'%')
                        ->orWhere('firstname', 'like', '%'.$q.'%')
                        ->orWhere('othernames', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate(20);

        // Fetch filter options
        $faculties = Faculty::orderBy('name')->get();
        
        $departments = Department::query()
            ->when($this->facultyFilter, fn ($q) => $q->where('faculty_id', $this->facultyFilter))
            ->orderBy('name')
            ->get();

        $programs = Program::query()
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.students.student-index', [
            'students' => $students,
            'approvalFilter' => $approval,
            'faculties' => $faculties,
            'departments' => $departments,
            'programs' => $programs,
            'allPrograms' => Program::orderBy('name')->get(), // for edit modal dropdown
        ])->layout('components.layouts.admin', [
            'title' => __('Students'),
            'headerTitle' => __('Students Directory'),
            'headerDescription' => __('Search, filter, edit, and delete student accounts.'),
        ]);
    }
}

