<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Actions\Staff\CreateTeacherUser;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Services\TeacherSpreadsheetImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherListPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public bool $showCreateModal = false;

    public bool $showImportModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingTeacherId = null;

    public ?int $deletingTeacherId = null;

    // Filters
    public string $search = '';

    public ?int $filterDepartment = null;

    public string $filterStatus = 'all';

    public bool $showDeleted = false;

    // Form fields
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $lastname = '';

    public string $othernames = '';

    public string $staff_id = '';

    public ?int $department_id = null;

    public string $phone_number = '';

    public bool $active = true;

    /** Filepond temp relative path */
    public string $importPath = '';

    /** @var list<string> */
    public array $importErrors = [];

    public int $importCreatedCount = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => null],
        'filterStatus' => ['except' => 'all'],
        'showDeleted' => ['except' => false],
    ];

    public function mount(): void
    {
        if (request()->boolean('create')) {
            $this->openCreateModal();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingShowDeleted(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetTeacherForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetTeacherForm();
    }

    public function openImportModal(): void
    {
        $this->importPath = '';
        $this->importErrors = [];
        $this->importCreatedCount = 0;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function openEditModal(int $teacherId): void
    {
        $teacher = Teacher::withTrashed()->with('user')->findOrFail($teacherId);
        $user = $teacher->user;
        if ($user === null) {
            return;
        }

        $this->editingTeacherId = $teacher->id;
        $this->name = (string) ($user->name ?? '');
        $this->email = (string) ($user->email ?? '');
        $this->password = '';
        $this->lastname = (string) ($teacher->lastname ?? '');
        $this->othernames = (string) ($teacher->othernames ?? '');
        $this->staff_id = (string) ($teacher->staff_id ?? '');
        $this->department_id = $teacher->department_id;
        $this->phone_number = (string) ($teacher->phone_number ?? '');
        $this->active = (bool) $user->active;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingTeacherId = null;
        $this->resetTeacherForm();
    }

    public function saveEdit(): void
    {
        if ($this->editingTeacherId === null) {
            return;
        }

        $teacher = Teacher::withTrashed()->with('user')->findOrFail($this->editingTeacherId);
        $user = $teacher->user;
        if ($user === null) {
            return;
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email,'.$user->id],
            'lastname' => ['required', 'string', 'max:255'],
            'othernames' => ['nullable', 'string', 'max:255'],
            'staff_id' => ['required', 'string', 'max:100', 'unique:'.User::class.',username,'.$user->id],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'active' => ['boolean'],
        ];

        if ($this->password !== '') {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validated = $this->validate($rules);

        DB::transaction(function () use ($user, $teacher, $validated): void {
            $user->forceFill([
                'name' => $validated['name'],
                'username' => $validated['staff_id'],
                'email' => $validated['email'],
                'active' => (bool) $validated['active'],
            ]);
            if ($this->password !== '') {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            $teacher->forceFill([
                'lastname' => $validated['lastname'],
                'othernames' => $validated['othernames'] ?? null,
                'staff_id' => $validated['staff_id'],
                'department_id' => isset($validated['department_id']) ? (int) $validated['department_id'] : null,
                'phone_number' => ($validated['phone_number'] ?? '') !== '' ? $validated['phone_number'] : null,
            ])->save();
        });

        $this->closeEditModal();
        $this->collegeToast(__('Teacher updated.'));
    }

    public function openDeleteModal(int $teacherId): void
    {
        $this->deletingTeacherId = $teacherId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingTeacherId = null;
    }

    public function confirmDelete(): void
    {
        if ($this->deletingTeacherId === null) {
            return;
        }

        $teacher = Teacher::query()->with('user')->findOrFail($this->deletingTeacherId);

        DB::transaction(function () use ($teacher): void {
            $teacher->delete();
            if ($teacher->user !== null) {
                $teacher->user->update(['active' => false]);
            }
        });

        $this->closeDeleteModal();
        $this->resetPage();
        $this->collegeToast(__('Teacher archived and user credentials deactivated.'));
    }

    public function restoreTeacher(int $id): void
    {
        $teacher = Teacher::withTrashed()->with('user')->findOrFail($id);

        DB::transaction(function () use ($teacher): void {
            $teacher->restore();
            if ($teacher->user !== null) {
                $teacher->user->update(['active' => true]);
            }
        });

        $this->collegeToast(__('Teacher restored and user credentials reactivated.'));
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importPath = '';
        $this->importErrors = [];
    }

    public function saveCreate(CreateTeacherUser $createTeacherUser): void
    {
        $validated = $this->validate([
            'lastname' => ['required', 'string', 'max:255'],
            'othernames' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'password' => ['nullable', 'string', 'min:8'],
            'staff_id' => ['required', 'string', 'max:100', 'unique:'.User::class.',username'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ]);

        $passwordToUse = ($validated['password'] ?? '') !== '' ? $validated['password'] : 'Password@1';

        $createTeacherUser->execute([
            'name' => $validated['name'],
            'username' => $validated['staff_id'],
            'email' => $validated['email'],
            'password' => $passwordToUse,
            'lastname' => $validated['lastname'],
            'othernames' => $validated['othernames'] ?? null,
            'staff_id' => $validated['staff_id'],
            'department_id' => isset($validated['department_id']) ? (int) $validated['department_id'] : null,
            'phone_number' => ($validated['phone_number'] ?? '') !== '' ? $validated['phone_number'] : null,
            'active' => true,
        ]);

        $this->closeCreateModal();
        $this->resetPage();
        $this->collegeToast(__('Teacher account created or updated (upserted).'));
    }

    public function runImport(TeacherSpreadsheetImportService $importService): void
    {
        $this->importErrors = [];
        $this->importCreatedCount = 0;

        $this->validate([
            'importPath' => ['required', 'string'],
        ]);

        $uid = auth()->id();
        if ($uid === null) {
            return;
        }

        $result = $importService->importFromFilepondRelativePath($this->importPath, (int) $uid);

        $this->importCreatedCount = $result['created'];
        $this->importErrors = $result['errors'];

        if ($result['created'] > 0) {
            $this->resetPage();
            $this->collegeToast(__('Processed :n teacher account(s) (created or updated).', ['n' => $result['created']]));
        }

        if ($result['errors'] !== [] && $result['created'] === 0) {
            $this->collegeToast($result['errors'][0], 'error');
        }
    }

    private function resetTeacherForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->lastname = '';
        $this->othernames = '';
        $this->staff_id = '';
        $this->department_id = null;
        $this->phone_number = '';
        $this->active = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Teacher::query();

        if ($this->showDeleted) {
            $query->onlyTrashed();
        } else {
            $query->withTrashed();
        }

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where('lastname', 'like', $term)
                  ->orWhere('othernames', 'like', $term)
                  ->orWhere('staff_id', 'like', $term)
                  ->orWhereHas('user', function ($uq) use ($term): void {
                      $uq->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                  });
            });
        }

        if ($this->filterDepartment !== null && $this->filterDepartment > 0) {
            $query->where('department_id', $this->filterDepartment);
        }

        if ($this->filterStatus === 'active') {
            $query->whereHas('user', fn ($q) => $q->where('active', true));
        } elseif ($this->filterStatus === 'inactive') {
            $query->whereHas('user', fn ($q) => $q->where('active', false));
        }

        $teachers = $query->with(['user', 'department'])
            ->orderBy('lastname')
            ->paginate(20);

        $departments = Department::query()->orderBy('name')->get();

        return view('livewire.admin.staff.teacher-list-page', [
            'teachers' => $teachers,
            'departments' => $departments,
        ])->layout('components.layouts.admin', [
            'title' => __('Teachers'),
            'headerTitle' => __('Teachers'),
            'headerDescription' => __('Manage teaching faculty, staff qualifications, and departmental affiliations.'),
        ]);
    }
}
