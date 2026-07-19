<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Models\DisciplinaryRecord;
use App\Models\Program;
use App\Models\Student;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DisciplineRecordsPage extends Component
{
    use WithPagination, DispatchesCollegeToasts;

    public string $search = '';

    public string $programFilter = '';

    public string $returnStatus = 'all';

    public string $studentPickerSearch = '';

    /** @var list<array{id:int, label:string, has_program:bool}> */
    public array $studentPickerHits = [];

    public ?int $disciplineStudentId = null;

    public string $offense = '';

    public string $action_taken = '';

    public string $comments = '';

    public string $date_of_action = '';

    public string $return_date = '';

    public ?int $closingRecordId = null;

    public ?DisciplinaryRecord $selectedCase = null;

    public ?int $editRecordId = null;

    public string $editComments = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('nav_students_discipline'), 403);
        $this->date_of_action = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatingReturnStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStudentPickerSearch(): void
    {
        $q = trim($this->studentPickerSearch);
        if ($q === '') {
            $this->studentPickerHits = [];

            return;
        }

        $admin = auth()->user()?->admin;

        $this->studentPickerHits = Student::query()
            ->where(function ($inner) use ($q): void {
                $inner
                    ->where('index_number', 'like', '%'.$q.'%')
                    ->orWhere('admission_index', 'like', '%'.$q.'%')
                    ->orWhere('lastname', 'like', '%'.$q.'%')
                    ->orWhere('firstname', 'like', '%'.$q.'%')
                    ->orWhere('othernames', 'like', '%'.$q.'%');
            })
            ->when($admin?->department_id, fn ($query) => $query->where('department_id', $admin->department_id))
            ->when($admin?->faculty_id, fn ($query) => $query->whereHas('department', fn ($d) => $d->where('faculty_id', $admin->faculty_id)))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(15)
            ->get()
            ->map(fn (Student $s): array => [
                'id' => $s->id,
                'label' => $s->index_number.' — '.trim(implode(' ', array_filter([$s->firstname, $s->othernames, $s->lastname]))),
                'has_program' => $s->program_id !== null,
            ])
            ->all();
    }

    public function selectDisciplineStudent(int $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessStudent($student), 403);
        }

        $this->disciplineStudentId = $studentId;
        $this->studentPickerHits = [];
        $this->studentPickerSearch = '';
    }

    public function clearDisciplineStudent(): void
    {
        $this->disciplineStudentId = null;
        $this->studentPickerSearch = '';
        $this->studentPickerHits = [];
    }

    public function openLogModal(): void
    {
        $this->reset(['offense', 'action_taken', 'comments', 'return_date', 'disciplineStudentId', 'studentPickerSearch', 'studentPickerHits']);
        $this->date_of_action = now()->toDateString();
        $this->dispatch('open-modal', 'log-disciplinary-modal');
    }

    public function viewCase(int $id): void
    {
        $case = DisciplinaryRecord::query()->with(['program'])->findOrFail($id);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($case->program_id && $admin->canAccessDepartment($case->program?->department_id), 403);
        }

        $this->selectedCase = $case;
        $this->dispatch('open-modal', 'view-case-modal');
    }

    public function confirmCloseFromView(): void
    {
        if ($this->selectedCase) {
            $admin = auth()->user()?->admin;
            if ($admin) {
                abort_unless($this->selectedCase->program_id && $admin->canAccessDepartment($this->selectedCase->program?->department_id), 403);
            }

            $this->closingRecordId = $this->selectedCase->id;
            $this->dispatch('close-modal', 'view-case-modal');
            $this->dispatch('open-modal', 'close-case-confirm-modal');
        }
    }

    public function startEditComments(int $id): void
    {
        $record = DisciplinaryRecord::query()->findOrFail($id);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($record->program_id && $admin->canAccessDepartment($record->program?->department_id), 403);
        }

        $this->editRecordId = $id;
        $this->editComments = $record->comments ?? '';
        $this->dispatch('close-modal', 'view-case-modal');
        $this->dispatch('open-modal', 'edit-comments-modal');
    }

    public function saveComments(): void
    {
        $this->validate([
            'editComments' => ['nullable', 'string', 'max:65535'],
        ]);

        if ($this->editRecordId) {
            $record = DisciplinaryRecord::query()->findOrFail($this->editRecordId);
            $admin = auth()->user()?->admin;
            if ($admin) {
                abort_unless($record->program_id && $admin->canAccessDepartment($record->program?->department_id), 403);
            }

            $record->comments = trim($this->editComments) !== '' ? trim($this->editComments) : null;
            $record->save();

            $this->dispatch('close-modal', 'edit-comments-modal');
            $this->collegeToast(__('Comments updated successfully.'));
            $this->reset(['editRecordId', 'editComments']);
        }
    }

    public function addRecord(): void
    {
        $this->validate([
            'disciplineStudentId' => ['required', 'integer', 'exists:students,id'],
            'offense' => ['required', 'string', 'max:65535'],
            'action_taken' => ['required', 'string', 'max:65535'],
            'comments' => ['nullable', 'string', 'max:65535'],
            'date_of_action' => ['required', 'date'],
            'return_date' => ['nullable', 'date'],
        ], [], [
            'disciplineStudentId' => __('student'),
        ]);

        $student = Student::query()->findOrFail($this->disciplineStudentId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessStudent($student), 403);
        }

        if ($student->program_id === null) {
            $this->addError('disciplineStudentId', __('Student has no program assigned.'));

            return;
        }

        $fullname = trim(implode(' ', array_filter([
            $student->lastname,
            $student->firstname,
            $student->othernames,
        ])));

        DisciplinaryRecord::query()->create([
            'index_number' => $student->index_number,
            'fullname' => $fullname !== '' ? $fullname : $student->index_number,
            'program_id' => $student->program_id,
            'offense' => $this->offense,
            'action_taken' => $this->action_taken,
            'comments' => $this->comments !== '' ? $this->comments : null,
            'date_of_action' => $this->date_of_action,
            'return_date' => $this->return_date !== '' ? $this->return_date : null,
            'return_status' => false,
        ]);

        $this->dispatch('close-modal', 'log-disciplinary-modal');
        $this->reset(['offense', 'action_taken', 'comments', 'return_date', 'disciplineStudentId', 'studentPickerSearch', 'studentPickerHits']);
        $this->date_of_action = now()->toDateString();
        $this->resetPage();

        $this->collegeToast(__('Disciplinary record added.'));
    }

    public function confirmCloseRecord(int $id): void
    {
        $record = DisciplinaryRecord::query()->findOrFail($id);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($record->program_id && $admin->canAccessDepartment($record->program?->department_id), 403);
        }

        $this->closingRecordId = $id;
        $this->dispatch('open-modal', 'close-case-confirm-modal');
    }

    public function closeRecord(): void
    {
        if ($this->closingRecordId === null) {
            return;
        }

        $record = DisciplinaryRecord::query()->findOrFail($this->closingRecordId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($record->program_id && $admin->canAccessDepartment($record->program?->department_id), 403);
        }

        if ($record->return_status) {
            return;
        }

        DB::transaction(function () use ($record): void {
            $record->forceFill([
                'return_status' => true,
                'return_date' => now()->toDateString(),
            ])->save();
        });

        $this->closingRecordId = null;
        $this->resetPage();

        $this->collegeToast(__('Case closed.'));
    }

    public function render(): View
    {
        $admin = auth()->user()?->admin;
        $programId = (int) $this->programFilter;

        $rows = DisciplinaryRecord::query()
            ->with(['program'])
            ->when(trim($this->search) !== '', function ($q): void {
                $s = trim($this->search);
                $q->where(function ($inner) use ($s): void {
                    $inner
                        ->where('index_number', 'like', '%'.$s.'%')
                        ->orWhere('fullname', 'like', '%'.$s.'%')
                        ->orWhere('offense', 'like', '%'.$s.'%');
                });
            })
            ->when($programId > 0, fn ($q) => $q->where('program_id', $programId))
            ->when($this->returnStatus === 'open', fn ($q) => $q->where('return_status', false))
            ->when($this->returnStatus === 'closed', fn ($q) => $q->where('return_status', true))
            ->when($admin?->department_id, fn ($q) => $q->whereHas('program', fn ($p) => $p->where('department_id', $admin->department_id)))
            ->when($admin?->faculty_id, fn ($q) => $q->whereHas('program.department', fn ($d) => $d->where('faculty_id', $admin->faculty_id)))
            ->orderByDesc('date_of_action')
            ->paginate(20);

        $selectedStudent = $this->disciplineStudentId
            ? Student::query()->find($this->disciplineStudentId)
            : null;

        $programs = Program::query()
            ->when($admin?->department_id, fn ($q) => $q->where('department_id', $admin->department_id))
            ->when($admin?->faculty_id, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $admin->faculty_id)))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.students.discipline-records-page', [
            'rows' => $rows,
            'programs' => $programs,
            'selectedStudent' => $selectedStudent,
        ])->layout('components.layouts.admin', [
            'title' => __('Disciplinary Records'),
            'headerTitle' => __('Disciplinary Records'),
            'headerDescription' => __('Log incidents, track suspension statuses, and manage student behavior archives.'),
        ]);
    }
}
