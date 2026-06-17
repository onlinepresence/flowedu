<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Semester;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SessionIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public ?int $editingId = null;

    public ?int $deletingSessionId = null;

    public string $formName = '';

    public string $formStartDate = '';

    public string $formEndDate = '';

    public bool $formIsCurrent = false;

    /** @var array<int, array{name: string, start_date: string, end_date: string, is_active: bool}> */
    public array $semesterRows = [];

    #[On('open-create-session')]
    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formStartDate = '';
        $this->formEndDate = '';
        $this->formIsCurrent = false;
        $this->semesterRows = [
            ['name' => '', 'start_date' => '', 'end_date' => '', 'is_active' => false],
        ];
        $this->resetValidation();
        $this->dispatchOpenSessionModal();
    }

    public function openEdit(int $sessionId): void
    {
        $session = AcademicSession::query()->with('semesters')->findOrFail($sessionId);
        if ($this->isOldSession($session)) {
            $this->collegeToast(__('Cannot edit completed/old academic sessions.'), 'error');
            return;
        }
        $this->editingId = $session->id;
        $this->formName = (string) ($session->name ?? '');
        $this->formStartDate = $session->start_date?->format('Y-m-d') ?? '';
        $this->formEndDate = $session->end_date?->format('Y-m-d') ?? '';
        $this->formIsCurrent = (bool) $session->is_current;
        $this->semesterRows = [];
        foreach ($session->semesters as $sem) {
            $this->semesterRows[] = [
                'name' => (string) ($sem->name ?? ''),
                'start_date' => $sem->start_date?->format('Y-m-d') ?? '',
                'end_date' => $sem->end_date?->format('Y-m-d') ?? '',
                'is_active' => (bool) $sem->is_active,
            ];
        }
        if ($this->semesterRows === []) {
            $this->semesterRows = [
                ['name' => '', 'start_date' => '', 'end_date' => '', 'is_active' => false],
            ];
        }
        $this->resetValidation();
        $this->dispatchOpenSessionModal();
    }

    public function cancelSessionModal(): void
    {
        $this->dispatch('close-modal', 'manage-session');
    }

    public function addSemesterRow(): void
    {
        $this->semesterRows[] = ['name' => '', 'start_date' => '', 'end_date' => '', 'is_active' => false];
    }

    public function removeSemesterRow(int $index): void
    {
        unset($this->semesterRows[$index]);
        $this->semesterRows = array_values($this->semesterRows);
        if ($this->semesterRows === []) {
            $this->semesterRows = [
                ['name' => '', 'start_date' => '', 'end_date' => '', 'is_active' => false],
            ];
        }
    }

    public function saveSession(): void
    {
        $this->validate([
            'formName' => ['required', 'string', 'max:20'],
            'formStartDate' => ['required', 'date'],
            'formEndDate' => ['required', 'date', 'after_or_equal:formStartDate'],
            'formIsCurrent' => ['boolean'],
            'semesterRows' => ['array'],
            'semesterRows.*.name' => ['nullable', 'string', 'max:50'],
            'semesterRows.*.start_date' => ['nullable', 'date'],
            'semesterRows.*.end_date' => ['nullable', 'date'],
            'semesterRows.*.is_active' => ['boolean'],
        ]);

        foreach ($this->semesterRows as $i => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $sd = trim((string) ($row['start_date'] ?? ''));
            $ed = trim((string) ($row['end_date'] ?? ''));
            if ($name !== '' || $sd !== '' || $ed !== '') {
                if ($name === '') {
                    $this->addError("semesterRows.{$i}.name", __('Each term needs a name when dates are set.'));
                }
                if ($sd === '') {
                    $this->addError("semesterRows.{$i}.start_date", __('Start date is required for this term.'));
                }
                if ($ed === '') {
                    $this->addError("semesterRows.{$i}.end_date", __('End date is required for this term.'));
                }
                if ($sd !== '' && $ed !== '' && $ed < $sd) {
                    $this->addError("semesterRows.{$i}.end_date", __('End date must be on or after the start date.'));
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $normalizedSemesters = [];
        $activeIndex = null;
        foreach ($this->semesterRows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $idx = count($normalizedSemesters);
            $wantActive = ! empty($row['is_active']);
            if ($wantActive && $activeIndex === null) {
                $activeIndex = $idx;
            }
            $normalizedSemesters[] = [
                'name' => $name,
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'is_active' => false,
            ];
        }
        if ($activeIndex !== null) {
            $normalizedSemesters[$activeIndex]['is_active'] = true;
        }

        DB::transaction(function () use ($normalizedSemesters): void {
            if ($this->formIsCurrent) {
                AcademicSession::query()->update(['is_current' => false]);
            }

            $payload = [
                'name' => trim($this->formName),
                'start_date' => $this->formStartDate,
                'end_date' => $this->formEndDate,
                'is_current' => $this->formIsCurrent,
            ];

            if ($this->editingId !== null) {
                $session = AcademicSession::query()->findOrFail($this->editingId);
                $session->update($payload);
            } else {
                $session = AcademicSession::query()->create($payload);
            }

            Semester::query()->where('academic_session_id', $session->id)->delete();
            foreach ($normalizedSemesters as $sem) {
                Semester::query()->create([
                    'academic_session_id' => $session->id,
                    'name' => $sem['name'],
                    'start_date' => $sem['start_date'],
                    'end_date' => $sem['end_date'],
                    'is_active' => $sem['is_active'],
                ]);
            }
        });

        $this->collegeToast(__('Academic session saved.'));
        $this->cancelSessionModal();
        $this->resetPage();
    }

    public function setCurrent(int $sessionId): void
    {
        $session = AcademicSession::findOrFail($sessionId);
        if ($this->isOldSession($session)) {
            $this->collegeToast(__('Cannot reactivate completed/old academic sessions.'), 'error');
            return;
        }
        DB::transaction(function () use ($sessionId): void {
            AcademicSession::query()->update(['is_current' => false]);
            AcademicSession::query()->whereKey($sessionId)->update(['is_current' => true]);
        });
        $this->collegeToast(__('Current academic session updated.'));
    }

    public function confirmDeleteSession(int $sessionId): void
    {
        $session = AcademicSession::findOrFail($sessionId);
        if (!$this->canDeleteSession($session)) {
            $this->collegeToast(__('Cannot delete an academic session that has already started or is completed.'), 'error');
            return;
        }
        $this->deletingSessionId = $sessionId;
        $this->dispatch('open-modal', 'confirm-delete-session-modal');
    }

    public function deleteSession(): void
    {
        if ($this->deletingSessionId === null) {
            return;
        }
        $session = AcademicSession::findOrFail($this->deletingSessionId);
        if (!$this->canDeleteSession($session)) {
            $this->collegeToast(__('Cannot delete an academic session that has already started or is completed.'), 'error');
            $this->deletingSessionId = null;
            return;
        }
        $session->delete();
        $this->deletingSessionId = null;
        $this->collegeToast(__('Academic session deleted.'));
        $this->resetPage();
    }

    public function isOldSession(AcademicSession $session): bool
    {
        if ($session->is_current) {
            return false;
        }
        if ($session->end_date && $session->end_date->startOfDay()->lt(now()->startOfDay())) {
            return true;
        }
        return false;
    }

    public function canDeleteSession(AcademicSession $session): bool
    {
        if ($session->is_current) {
            return false;
        }
        if ($session->start_date && $session->start_date->startOfDay()->lte(now()->startOfDay())) {
            return false;
        }
        if ($this->isOldSession($session)) {
            return false;
        }
        return true;
    }

    public function render(): View
    {
        $sessions = AcademicSession::query()
            ->with('semesters')
            ->orderByDesc('start_date')
            ->paginate(20);

        return view('livewire.admin.academic.session-index', [
            'sessions' => $sessions,
        ])->layout('components.layouts.admin', [
            'title' => __('Sessions'),
            'headerTitle' => __('Academic Sessions'),
            'headerDescription' => __('Manage academic sessions, current terms/semesters, and specify active periods.'),
        ]);
    }

    private function dispatchOpenSessionModal(): void
    {
        $this->dispatch('open-modal', 'manage-session');
    }
}
