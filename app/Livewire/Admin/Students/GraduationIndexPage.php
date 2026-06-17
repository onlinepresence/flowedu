<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Models\AcademicSession;
use App\Models\Graduation;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Student;
use App\Services\Students\ProcessGraduationService;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class GraduationIndexPage extends Component
{
    use WithPagination, DispatchesCollegeToasts;

    public string $processLevel = '400';

    public string $processProgramId = '';

    public string $processSessionId = '';

    public string $graduationDate = '';

    public string $listProgramFilter = '';

    public string $listSessionFilter = '';

    /** @var array<string, bool> */
    public array $clearanceDepartments = [];

    /** @var array<string, bool> */
    public array $clearanceNotRequired = [];

    public function mount(): void
    {
        $this->graduationDate = now()->toDateString();
        $current = AcademicSession::query()->where('is_current', true)->first();
        $this->processSessionId = $current ? (string) $current->getKey() : '';
        $this->listSessionFilter = $current ? (string) $current->id : '';
        $this->loadClearanceConfiguration();
    }

    public function updatingListProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatingListSessionFilter(): void
    {
        $this->resetPage();
    }

    public function refreshStats(): void
    {
        $this->collegeToast(__('Statistics refreshed.'));
    }

    public function processGraduation(ProcessGraduationService $service): void
    {
        $this->validate([
            'processLevel' => ['required', 'in:400'],
            'processProgramId' => ['nullable'],
            'processSessionId' => ['nullable'],
            'graduationDate' => ['required', 'date'],
        ]);

        $sessionId = (int) $this->processSessionId;
        if ($sessionId < 1) {
            $current = AcademicSession::query()->where('is_current', true)->first();
            if ($current === null) {
                $this->addError('processSessionId', __('Select an academic session or set a current session.'));

                return;
            }
            $sessionId = (int) $current->getKey();
        } else {
            $exists = AcademicSession::query()->whereKey($sessionId)->exists();
            if (! $exists) {
                $this->addError('processSessionId', __('Invalid academic session.'));

                return;
            }
        }

        $programId = (int) $this->processProgramId;

        $count = $service->run(
            $sessionId,
            '400',
            $programId > 0 ? $programId : null,
            $this->graduationDate,
            (int) auth()->id(),
        );

        $this->resetPage();

        $this->collegeToast(__(':count student(s) graduated successfully.', ['count' => $count]));
    }

    public function saveClearanceConfiguration(): void
    {
        $catalog = config('clearance.definitions', []);
        $selected = [];
        foreach ($catalog as $key => $_label) {
            if (($this->clearanceDepartments[$key] ?? false) === true) {
                $selected[] = $key;
            }
        }

        if ($selected === []) {
            $this->addError('clearanceDepartments', __('Select at least one clearance department.'));

            return;
        }

        $notRequired = [];
        foreach ($selected as $key) {
            if (($this->clearanceNotRequired[$key] ?? false) === true) {
                $notRequired[] = $key;
            }
        }

        $uid = auth()->id();
        Setting::query()->updateOrCreate(
            ['setting_key' => 'clearance.department_keys'],
            [
                'category' => 'clearance',
                'setting_value' => (string) json_encode($selected),
                'data_type' => 'json',
                'description' => 'Enabled clearance departments',
                'updated_by' => $uid,
            ]
        );

        Setting::query()->updateOrCreate(
            ['setting_key' => 'clearance.default_not_required_keys'],
            [
                'category' => 'clearance',
                'setting_value' => (string) json_encode($notRequired),
                'data_type' => 'json',
                'description' => 'Clearance departments defaulting to not required',
                'updated_by' => $uid,
            ]
        );

        $this->dispatch('close-modal', 'clearance-settings-modal');
        $this->collegeToast(__('Clearance configuration saved.'));
    }

    public function render(): View
    {
        $currentSession = AcademicSession::query()->where('is_current', true)->first();

        $statsTotal = Graduation::query()->count();
        $statsThisYear = Graduation::query()
            ->whereYear('graduation_date', now()->year)
            ->count();

        $eligiblePreview = Student::query()
            ->where('approved', true)
            ->where('graduated', false)
            ->where('current_year', '400')
            ->when((int) $this->processProgramId > 0, fn ($q) => $q->where('program_id', (int) $this->processProgramId))
            ->count();

        $listProgramId = (int) $this->listProgramFilter;
        $listSessionId = (int) $this->listSessionFilter;

        $rows = Graduation::query()
            ->with(['student.program'])
            ->when($listProgramId > 0, function ($q) use ($listProgramId): void {
                $q->whereHas('student', fn ($s) => $s->where('program_id', $listProgramId));
            })
            ->when($listSessionId > 0, fn ($q) => $q->where('academic_session_id', $listSessionId))
            ->orderByDesc('graduation_date')
            ->paginate(20);

        $sessions = AcademicSession::query()->orderByDesc('id')->get();

        return view('livewire.admin.students.graduation-index-page', [
            'currentSession' => $currentSession,
            'statsTotal' => $statsTotal,
            'statsThisYear' => $statsThisYear,
            'eligiblePreview' => $eligiblePreview,
            'programs' => Program::query()->orderBy('name')->get(),
            'sessions' => $sessions,
            'rows' => $rows,
            'clearanceCatalog' => config('clearance.definitions', []),
        ])->layout('components.layouts.admin', [
            'title' => __('Graduation'),
            'headerTitle' => __('Graduation Management'),
            'headerDescription' => __('Configure clearance checks, run graduation process, and browse graduated students records.'),
        ]);
    }

    private function loadClearanceConfiguration(): void
    {
        $catalog = config('clearance.definitions', []);
        $rawSelected = Setting::query()
            ->where('setting_key', 'clearance.department_keys')
            ->value('setting_value');
        $rawNotRequired = Setting::query()
            ->where('setting_key', 'clearance.default_not_required_keys')
            ->value('setting_value');

        $selected = is_string($rawSelected) ? json_decode($rawSelected, true) : null;
        $notRequired = is_string($rawNotRequired) ? json_decode($rawNotRequired, true) : null;

        $selectedKeys = is_array($selected) && $selected !== [] ? $selected : array_keys($catalog);
        $notRequiredKeys = is_array($notRequired) ? $notRequired : config('clearance.default_not_required_keys', []);

        foreach ($catalog as $key => $_label) {
            $this->clearanceDepartments[$key] = in_array($key, $selectedKeys, true);
            $this->clearanceNotRequired[$key] = in_array($key, $notRequiredKeys, true);
        }
    }
}
