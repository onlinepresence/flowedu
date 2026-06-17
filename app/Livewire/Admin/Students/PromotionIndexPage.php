<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Student;
use App\Services\Students\ManualPromotionService;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PromotionIndexPage extends Component
{
    use WithPagination, DispatchesCollegeToasts;

    public string $promotionMode = 'auto';

    public string $fromLevel = '100';

    public string $toLevel = '200';

    public string $programFilter = '';

    public string $studentSearch = '';

    /** @var list<array{id:int, label:string}> */
    public array $studentSearchHits = [];

    /** @var list<int> */
    public array $manualPickIds = [];

    /** @var list<array{id:int, index_number:string, fullname:string, current_year:string, program_name:string}> */
    public array $previewList = [];

    /** @var list<int> */
    public array $previewStudentIds = [];

    public bool $showPreview = false;

    // Batch details properties
    public ?string $selectedBatchDate = null;
    public ?int $selectedBatchSessionId = null;
    public ?string $selectedBatchSessionName = null;
    public ?int $selectedBatchFromLevel = null;
    public ?int $selectedBatchToLevel = null;
    /** @var list<array{promotion_id:int, index_number:string, fullname:string}> */
    public array $batchStudents = [];

    public string $historySessionFilter = '';

    // Revert batch target parameters
    public ?string $revertBatchDate = null;
    public ?int $revertBatchSessionId = null;
    public ?int $revertBatchFromLevel = null;
    public ?int $revertBatchToLevel = null;

    public function mount(): void
    {
        $this->promotionMode = $this->readPromotionMode();
        $current = AcademicSession::query()->where('is_current', true)->first();
        if ($current) {
            $this->historySessionFilter = (string) $current->id;
        }
    }

    public function updatedStudentSearch(): void
    {
        $q = trim($this->studentSearch);
        $programId = (int) $this->programFilter;

        if ($q === '') {
            $this->studentSearchHits = [];

            return;
        }

        $this->studentSearchHits = Student::query()
            ->with('program')
            ->where(function ($inner) use ($q): void {
                $inner
                    ->where('index_number', 'like', '%'.$q.'%')
                    ->orWhere('admission_index', 'like', '%'.$q.'%')
                    ->orWhere('lastname', 'like', '%'.$q.'%')
                    ->orWhere('firstname', 'like', '%'.$q.'%')
                    ->orWhere('othernames', 'like', '%'.$q.'%');
            })
            ->when($programId > 0, fn ($query) => $query->where('program_id', $programId))
            ->orderBy('lastname')
            ->limit(15)
            ->get()
            ->map(fn (Student $s): array => [
                'id' => $s->id,
                'label' => $s->index_number.' — '.trim(implode(' ', array_filter([$s->firstname, $s->othernames, $s->lastname]))),
            ])
            ->all();
    }

    public function addManualPick(int $studentId): void
    {
        if (! in_array($studentId, $this->manualPickIds, true)) {
            $this->manualPickIds[] = $studentId;
        }
        $this->studentSearchHits = [];
        $this->studentSearch = '';
    }

    public function removeManualPick(int $studentId): void
    {
        $this->manualPickIds = array_values(array_filter(
            $this->manualPickIds,
            fn (int $id): bool => $id !== $studentId
        ));
    }

    public function savePromotionMode(): void
    {
        $this->validate([
            'promotionMode' => ['required', 'in:auto,manual'],
        ]);

        $row = Setting::query()->firstOrNew(['setting_key' => 'students.promotion_mode']);
        $row->forceFill([
            'setting_value' => $this->promotionMode,
            'category' => 'students',
            'data_type' => 'string',
            'description' => 'Student promotion: auto (cron) or manual (admin bulk)',
            'updated_by' => auth()->id(),
        ])->save();

        $this->dispatch('close-modal', 'promotion-settings-modal');
        $this->collegeToast(__('Promotion mode settings saved successfully.'));
    }

    public function previewPromotion(ManualPromotionService $manualPromotion): void
    {
        $current = AcademicSession::query()->where('is_current', true)->first();
        if ($current === null) {
            $this->addError('preview', __('Set a current academic session first.'));

            return;
        }

        $this->validate([
            'fromLevel' => ['required', 'in:100,200,300,400'],
            'toLevel' => ['required', 'in:100,200,300,400'],
            'programFilter' => ['nullable'],
        ]);

        if ($this->fromLevel === $this->toLevel) {
            $this->addError('toLevel', __('To level must differ from from level.'));

            return;
        }

        $programId = (int) $this->programFilter;
        $restrict = $this->manualPickIds;

        $rows = $manualPromotion->preview(
            $this->fromLevel,
            $programId > 0 ? $programId : null,
            $restrict,
        );

        $this->previewList = $rows->values()->all();
        $this->previewStudentIds = $rows->pluck('id')->all();
        $this->showPreview = true;
    }

    public function cancelPreview(): void
    {
        $this->showPreview = false;
        $this->previewList = [];
        $this->previewStudentIds = [];
    }

    public function confirmPromotion(ManualPromotionService $manualPromotion): void
    {
        $session = AcademicSession::query()->where('is_current', true)->first();
        if ($session === null) {
            $this->addError('confirm', __('Set a current academic session first.'));

            return;
        }

        $this->validate([
            'fromLevel' => ['required', 'in:100,200,300,400'],
            'toLevel' => ['required', 'in:100,200,300,400'],
            'previewStudentIds' => ['required', 'array', 'min:1'],
            'previewStudentIds.*' => ['integer', 'exists:students,id'],
        ]);

        $programId = (int) $this->programFilter;

        $count = $manualPromotion->confirm(
            $session,
            $this->fromLevel,
            $this->toLevel,
            $programId > 0 ? $programId : null,
            $this->previewStudentIds,
            (int) auth()->id(),
        );

        $this->cancelPreview();
        $this->dispatch('close-modal', 'manual-promotion-modal');
        $this->resetPage();

        $this->collegeToast(__(':count student(s) promoted.', ['count' => $count]));
    }

    public function viewBatch(string $date, int $sessionId, int $from, int $to): void
    {
        $this->selectedBatchDate = $date;
        $this->selectedBatchSessionId = $sessionId;
        $this->selectedBatchSessionName = AcademicSession::find($sessionId)?->name ?? '—';
        $this->selectedBatchFromLevel = $from;
        $this->selectedBatchToLevel = $to;

        $this->batchStudents = Promotion::query()
            ->with(['student'])
            ->where('promotion_date', $date)
            ->where('academic_session_id', $sessionId)
            ->where('from_level', $from)
            ->where('to_level', $to)
            ->get()
            ->map(fn ($p): array => [
                'promotion_id' => $p->id,
                'index_number' => $p->student?->index_number ?? '—',
                'fullname' => $p->student ? trim(implode(' ', array_filter([$p->student->firstname, $p->student->lastname]))) : '—',
            ])
            ->all();

        $this->dispatch('open-modal', 'view-batch-modal');
    }

    public function confirmRevertBatch(string $date, int $sessionId, int $from, int $to): void
    {
        $this->revertBatchDate = $date;
        $this->revertBatchSessionId = $sessionId;
        $this->revertBatchFromLevel = $from;
        $this->revertBatchToLevel = $to;
        $this->dispatch('open-modal', 'revert-batch-confirm-modal');
    }

    public function revertBatch(): void
    {
        if (! $this->revertBatchDate || ! $this->revertBatchSessionId || ! $this->revertBatchFromLevel || ! $this->revertBatchToLevel) {
            return;
        }

        DB::transaction(function (): void {
            $promotions = Promotion::query()
                ->where('promotion_date', $this->revertBatchDate)
                ->where('academic_session_id', $this->revertBatchSessionId)
                ->where('from_level', $this->revertBatchFromLevel)
                ->where('to_level', $this->revertBatchToLevel)
                ->get();

            foreach ($promotions as $promo) {
                $student = Student::query()->find($promo->student_id);
                if ($student) {
                    $student->current_year = (string) $this->revertBatchFromLevel;
                    $student->save();
                }
                $promo->delete();
            }
        });

        $this->reset(['revertBatchDate', 'revertBatchSessionId', 'revertBatchFromLevel', 'revertBatchToLevel']);
        $this->dispatch('close-modal', 'revert-batch-confirm-modal');
        $this->resetPage();
        $this->collegeToast(__('Promotion batch reverted successfully.'));
    }

    public function revertIndividualPromotion(int $promotionId): void
    {
        DB::transaction(function () use ($promotionId): void {
            $promo = Promotion::query()->findOrFail($promotionId);
            $student = Student::query()->find($promo->student_id);
            if ($student) {
                $student->current_year = (string) $promo->from_level;
                $student->save();
            }
            $promo->delete();
        });

        // Remove from current details list
        $this->batchStudents = array_values(array_filter(
            $this->batchStudents,
            fn (array $item): bool => $item['promotion_id'] !== $promotionId
        ));

        // If no students left in batch, close modal
        if (count($this->batchStudents) === 0) {
            $this->dispatch('close-modal', 'view-batch-modal');
        }

        $this->resetPage();
        $this->collegeToast(__('Student promotion reverted successfully.'));
    }

    protected function readPromotionMode(): string
    {
        $raw = Setting::query()
            ->where('setting_key', 'students.promotion_mode')
            ->value('setting_value');

        return $raw === 'manual' ? 'manual' : 'auto';
    }

    public function render(): View
    {
        $currentSession = AcademicSession::query()
            ->where('is_current', true)
            ->with(['semesters' => fn ($q) => $q->where('is_active', true)])
            ->first();

        $activeSemester = $currentSession?->semesters->first();

        // Query history grouped by batches
        $batches = Promotion::query()
            ->select(
                'promotion_date',
                'academic_session_id',
                'from_level',
                'to_level',
                'promoted_by',
                DB::raw('count(*) as student_count')
            )
            ->with(['academicSession', 'promotedBy'])
            ->when($this->historySessionFilter !== '', fn ($q) => $q->where('academic_session_id', (int) $this->historySessionFilter))
            ->groupBy('promotion_date', 'academic_session_id', 'from_level', 'to_level', 'promoted_by')
            ->orderByDesc('promotion_date')
            ->paginate(15);

        return view('livewire.admin.students.promotion-index-page', [
            'currentSession' => $currentSession,
            'activeSemester' => $activeSemester,
            'programs' => Program::query()->orderBy('name')->get(),
            'batches' => $batches,
            'sessions' => AcademicSession::query()->orderByDesc('is_current')->orderByDesc('id')->get(),
            'manualPickStudents' => $this->manualPickIds !== []
                ? Student::query()->whereIn('id', $this->manualPickIds)->get()->keyBy('id')
                : collect(),
        ])->layout('components.layouts.admin', [
            'title' => __('Student Promotion'),
            'headerTitle' => __('Student Promotion'),
            'headerDescription' => __('Promote student levels manually or manage automated academic transitions.'),
        ]);
    }
}
