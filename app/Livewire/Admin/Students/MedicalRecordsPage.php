<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Models\MedicalHistory;
use App\Models\Student;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class MedicalRecordsPage extends Component
{
    use WithPagination, DispatchesCollegeToasts;

    public string $search = '';

    public string $periodFilter = 'current_month';

    public ?string $customStartDate = null;

    public ?string $customEndDate = null;

    public string $studentPickerSearch = '';

    /** @var list<array{id:int, label:string}> */
    public array $studentPickerHits = [];

    public ?int $editStudentId = null;

    public ?int $editRecordId = null;

    public ?int $viewRecordId = null;

    public ?MedicalHistory $selectedRecord = null;

    public ?int $summaryStudentId = null;

    public ?Student $summaryStudent = null;

    /** @var list<MedicalHistory> */
    public array $summaryRecords = [];

    public string $allergies = '';

    public string $insurance_number = '';

    public ?string $blood_group = null;

    public string $medical_conditions = '';

    public string $medications = '';

    public string $immunization_records = '';

    public string $emergency_contacts = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCustomStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingCustomEndDate(): void
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

        $this->studentPickerHits = Student::query()
            ->where(function ($inner) use ($q): void {
                $inner
                    ->where('index_number', 'like', '%'.$q.'%')
                    ->orWhere('admission_index', 'like', '%'.$q.'%')
                    ->orWhere('lastname', 'like', '%'.$q.'%')
                    ->orWhere('firstname', 'like', '%'.$q.'%')
                    ->orWhere('othernames', 'like', '%'.$q.'%');
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(15)
            ->get()
            ->map(fn (Student $s): array => [
                'id' => $s->id,
                'label' => $s->index_number.' — '.trim(implode(' ', array_filter([$s->firstname, $s->othernames, $s->lastname]))),
            ])
            ->all();
    }

    public function selectMedicalStudent(int $studentId): void
    {
        $this->editStudentId = $studentId;
        $this->studentPickerHits = [];
        $this->studentPickerSearch = '';
        $this->loadFormFromStudent();
        $this->dispatch('open-modal', 'log-medical-modal');
    }

    public function openLogModal(): void
    {
        $this->clearMedicalStudent();
        $this->dispatch('open-modal', 'log-medical-modal');
    }

    public function clearMedicalStudent(): void
    {
        $this->editStudentId = null;
        $this->editRecordId = null;
        $this->reset([
            'allergies',
            'insurance_number',
            'blood_group',
            'medical_conditions',
            'medications',
            'immunization_records',
            'emergency_contacts',
            'studentPickerSearch',
            'studentPickerHits',
        ]);
    }

    protected function loadFormFromStudent(): void
    {
        if ($this->editStudentId === null) {
            return;
        }

        $student = Student::query()
            ->with(['parentGuardians'])
            ->findOrFail($this->editStudentId);

        $this->allergies = (string) ($student->allergy ?? '');
        $this->insurance_number = (string) ($student->insurance_number ?? '');
        $this->blood_group = $student->blood_group;

        // Load emergency contacts from the latest medical record first, if it exists
        $latestRecord = MedicalHistory::query()
            ->where('student_id', $this->editStudentId)
            ->orderByDesc('id')
            ->first();

        if ($latestRecord && ! empty($latestRecord->emergency_contacts)) {
            $this->emergency_contacts = $latestRecord->emergency_contacts;
        } else {
            // Fallback to the first parent guardian
            $parent = $student->parentGuardians->first();
            if ($parent) {
                $this->emergency_contacts = "{$parent->name} ({$parent->relationship}) — Phone: {$parent->phone_number}";
            } else {
                $this->emergency_contacts = '';
            }
        }

        $this->medical_conditions = '';
        $this->medications = '';
        $this->immunization_records = '';
    }

    public function editRecord(int $id): void
    {
        $this->editRecordId = $id;
        $record = MedicalHistory::query()->with('student')->findOrFail($id);
        $this->editStudentId = $record->student_id;
        $this->allergies = $record->allergies ?? '';
        $this->insurance_number = $record->student?->insurance_number ?? '';
        $this->blood_group = $record->student?->blood_group;
        $this->medical_conditions = $record->medical_conditions ?? '';
        $this->medications = $record->medications ?? '';
        $this->immunization_records = $record->immunization_records ?? '';
        $this->emergency_contacts = $record->emergency_contacts ?? '';

        $this->dispatch('close-modal', 'view-medical-modal');
        $this->dispatch('open-modal', 'log-medical-modal');
    }

    public function viewRecord(int $id): void
    {
        $this->viewRecordId = $id;
        $this->selectedRecord = MedicalHistory::query()->with('student')->findOrFail($id);
        $this->dispatch('open-modal', 'view-medical-modal');
    }

    public function viewSummary(int $studentId): void
    {
        $this->summaryStudentId = $studentId;
        $this->summaryStudent = Student::query()->findOrFail($studentId);
        $this->summaryRecords = MedicalHistory::query()
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()
            ->all();
        $this->dispatch('open-modal', 'view-summary-modal');
    }

    public function saveMedical(): void
    {
        $this->validate([
            'editStudentId' => ['required', 'integer', 'exists:students,id'],
            'allergies' => ['nullable', 'string', 'max:255'],
            'insurance_number' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'medical_conditions' => ['nullable', 'string', 'max:65535'],
            'medications' => ['nullable', 'string', 'max:65535'],
            'immunization_records' => ['nullable', 'string', 'max:65535'],
            'emergency_contacts' => ['nullable', 'string', 'max:65535'],
        ], [], [
            'editStudentId' => __('student'),
        ]);

        $allergyVal = trim($this->allergies);
        $insuranceVal = trim($this->insurance_number);

        DB::transaction(function () use ($allergyVal, $insuranceVal): void {
            if ($this->editRecordId) {
                $record = MedicalHistory::query()->findOrFail($this->editRecordId);
                $record->update([
                    'medical_conditions' => trim($this->medical_conditions) !== '' ? trim($this->medical_conditions) : null,
                    'allergies' => $allergyVal !== '' ? $allergyVal : null,
                    'medications' => trim($this->medications) !== '' ? trim($this->medications) : null,
                    'immunization_records' => trim($this->immunization_records) !== '' ? trim($this->immunization_records) : null,
                    'emergency_contacts' => trim($this->emergency_contacts) !== '' ? trim($this->emergency_contacts) : null,
                ]);
                $student = $record->student;
            } else {
                $student = Student::query()->findOrFail($this->editStudentId);
                MedicalHistory::query()->create([
                    'student_id' => $student->id,
                    'medical_conditions' => trim($this->medical_conditions) !== '' ? trim($this->medical_conditions) : null,
                    'allergies' => $allergyVal !== '' ? $allergyVal : null,
                    'medications' => trim($this->medications) !== '' ? trim($this->medications) : null,
                    'immunization_records' => trim($this->immunization_records) !== '' ? trim($this->immunization_records) : null,
                    'emergency_contacts' => trim($this->emergency_contacts) !== '' ? trim($this->emergency_contacts) : null,
                ]);
            }

            if ($student) {
                $student->allergy = $allergyVal !== '' ? $allergyVal : null;
                $student->insurance_number = $insuranceVal !== '' ? $insuranceVal : null;
                if (is_null($student->blood_group)) {
                    $student->blood_group = $this->blood_group ?: null;
                }
                $student->save();
            }
        });

        $this->dispatch('close-modal', 'log-medical-modal');
        $this->clearMedicalStudent();
        $this->collegeToast(__('Medical record saved successfully.'));
    }

    public function render(): View
    {
        $browseRows = MedicalHistory::query()
            ->with(['student'])
            ->when($this->periodFilter === 'current_month', function ($q): void {
                $q->where('created_at', '>=', now()->startOfMonth());
            })
            ->when($this->periodFilter === 'last_30_days', function ($q): void {
                $q->where('created_at', '>=', now()->subDays(30));
            })
            ->when($this->periodFilter === 'last_3_months', function ($q): void {
                $q->where('created_at', '>=', now()->subMonths(3));
            })
            ->when($this->periodFilter === 'last_6_months', function ($q): void {
                $q->where('created_at', '>=', now()->subMonths(6));
            })
            ->when($this->periodFilter === 'custom', function ($q): void {
                if ($this->customStartDate) {
                    $q->where('created_at', '>=', $this->customStartDate . ' 00:00:00');
                }
                if ($this->customEndDate) {
                    $q->where('created_at', '<=', $this->customEndDate . ' 23:59:59');
                }
            })
            ->when(trim($this->search) !== '', function ($q): void {
                $term = '%'.trim($this->search).'%';
                $q->whereHas('student', function ($s) use ($term): void {
                    $s->where('index_number', 'like', $term)
                        ->orWhere('firstname', 'like', $term)
                        ->orWhere('lastname', 'like', $term);
                });
            })
            ->orderByDesc('id')
            ->paginate(15);

        $selectedStudent = $this->editStudentId
            ? Student::query()->find($this->editStudentId)
            : null;

        return view('livewire.admin.students.medical-records-page', [
            'browseRows' => $browseRows,
            'selectedStudent' => $selectedStudent,
        ])->layout('components.layouts.admin', [
            'title' => __('Medical Records'),
            'headerTitle' => __('Medical Records'),
            'headerDescription' => __('View, update, and search student medical histories, insurance info, and emergency contacts.'),
        ]);
    }
}
