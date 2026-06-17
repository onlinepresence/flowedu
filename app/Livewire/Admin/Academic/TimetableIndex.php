<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableClass;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class TimetableIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $activeTab = 'existing'; // 'existing' or 'create'

    /** @var int|string|null */
    public $filterProgramId = null;

    /** @var int|string|null */
    public $filterLevel = null;

    /** @var int|string|null */
    public $filterSessionId = null;

    /** @var int|string|null */
    public $createProgramId = null;

    /** @var int|string|null */
    public $createLevel = null;

    /** @var int|string|null */
    public $createSessionId = null;

    public ?int $selectedTimetableId = null;

    /** @var list<int> */
    public array $selectedIds = [];

    public ?int $duplicateTargetSessionId = null;

    public ?int $slotTimetableId = null;

    public ?int $editingSlotId = null;

    public ?int $deletingTimetableId = null;

    public ?int $deletingSlotId = null;

    public string $slotDay = '';

    public string $slotStart = '';

    public string $slotEnd = '';

    public ?int $slotCourseId = null;

    public ?int $slotTeacherId = null;

    public string $slotVenue = '';

    public bool $slotConfirmTeacherReassign = false;

    /** @return list<string> */
    public static function weekDays(): array
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    }

    public function selectTimetable(?int $id): void
    {
        $this->selectedTimetableId = $id;
        $this->selectedIds = [];
    }

    public function updatedFilterProgramId(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function updatedFilterLevel(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function updatedFilterSessionId(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function loadOrCreateTimetable(): void
    {
        $this->validate([
            'createProgramId' => ['required', 'integer', 'exists:programs,id'],
            'createLevel' => ['required', 'integer', 'in:100,200,300,400'],
            'createSessionId' => ['required', 'integer', 'exists:academic_sessions,id'],
        ]);

        $timetable = Timetable::query()->firstOrCreate(
            [
                'program_id' => (int) $this->createProgramId,
                'level' => (int) $this->createLevel,
                'session_id' => (int) $this->createSessionId,
            ],
            [
                'created_by' => Auth::id(),
            ]
        );

        $this->collegeToast(__('Timetable loaded.'));
        $this->selectedTimetableId = $timetable->id;
        $this->activeTab = 'existing';
        $this->resetPage();
    }

    public function openSlotCreate(int $timetableId): void
    {
        $timetable = Timetable::query()->with('academicSession')->findOrFail($timetableId);
        if (!$timetable->academicSession->is_current) {
            $this->collegeToast(__('Historical timetables are read-only.'), 'error');
            return;
        }

        $this->slotTimetableId = $timetableId;
        $this->editingSlotId = null;
        $this->slotDay = '';
        $this->slotStart = '';
        $this->slotEnd = '';
        $this->slotCourseId = null;
        $this->slotTeacherId = null;
        $this->slotVenue = '';
        $this->slotConfirmTeacherReassign = false;
        $this->resetValidation();
        $this->dispatch('open-modal', 'timetable-slot');
    }

    public function openSlotEdit(int $slotId): void
    {
        $slot = TimetableClass::query()->with('timetable.academicSession')->findOrFail($slotId);
        if (!$slot->timetable->academicSession->is_current) {
            $this->collegeToast(__('Historical timetables are read-only.'), 'error');
            return;
        }

        $this->slotTimetableId = $slot->timetable_id;
        $this->editingSlotId = $slot->id;
        $this->slotDay = (string) ($slot->day ?? '');
        $this->slotStart = $slot->start_time ? Str::substr((string) $slot->start_time, 0, 5) : '';
        $this->slotEnd = $slot->end_time ? Str::substr((string) $slot->end_time, 0, 5) : '';
        $this->slotCourseId = $slot->course_id;
        $this->slotTeacherId = $slot->teacher_id;
        $this->slotVenue = (string) ($slot->venue ?? '');
        $this->slotConfirmTeacherReassign = false;
        $this->resetValidation();
        $this->dispatch('open-modal', 'timetable-slot');
    }

    public function cancelSlotModal(): void
    {
        $this->dispatch('close-modal', 'timetable-slot');
    }

    public function saveSlot(bool $exit = true): void
    {
        $timetable = Timetable::query()->with('academicSession')->findOrFail((int) $this->slotTimetableId);
        if (!$timetable->academicSession->is_current) {
            $this->addError('slotStart', __('Historical timetables are read-only.'));
            return;
        }

        $days = self::weekDays();

        $this->validate([
            'slotDay' => ['required', 'string', 'in:'.implode(',', $days)],
            'slotStart' => ['required', 'date_format:H:i'],
            'slotEnd' => ['required', 'date_format:H:i'],
            'slotCourseId' => ['required', 'integer', 'exists:courses,id'],
            'slotTeacherId' => ['required', 'integer', 'exists:teachers,id'],
            'slotVenue' => ['required', 'string', 'max:255'],
        ]);

        if (strcmp($this->slotEnd, $this->slotStart) <= 0) {
            $this->addError('slotEnd', __('End time must be after start time.'));
            return;
        }

        $course = Course::query()->findOrFail((int) $this->slotCourseId);
        if ((int) $course->program_id !== (int) $timetable->program_id) {
            $this->addError('slotCourseId', __('Course must belong to this timetable program.'));
            return;
        }

        $levelMap = [100 => 1, 200 => 2, 300 => 3, 400 => 4];
        $normalizedLevel = $levelMap[(int) ($timetable->level ?? 0)] ?? (int) ($timetable->level ?? 0);
        if ((int) $course->year_level !== (int) $normalizedLevel) {
            $this->addError('slotCourseId', __('Selected course is not assigned to this level.'));
            return;
        }

        if ($course->teacher_id !== null && (int) $course->teacher_id !== (int) $this->slotTeacherId && ! $this->slotConfirmTeacherReassign) {
            $this->addError('slotTeacherId', __('This course already has a teacher assigned. Enable reassignment to continue.'));
            return;
        }

        $overlapQuery = TimetableClass::query()
            ->where('day', $this->slotDay)
            ->where('start_time', '<', $this->slotEnd)
            ->where('end_time', '>', $this->slotStart);

        if ($this->editingSlotId !== null) {
            $overlapQuery->where('id', '!=', $this->editingSlotId);
        }

        $venueConflict = (clone $overlapQuery)
            ->where('venue', $this->slotVenue)
            ->where(function ($query): void {
                $query
                    ->where('teacher_id', '!=', $this->slotTeacherId)
                    ->orWhere('course_id', '!=', $this->slotCourseId);
            })
            ->exists();
        if ($venueConflict) {
            $this->addError('slotVenue', __('Venue conflict detected for this day/time. Only a joined class is allowed.'));
            return;
        }

        $sameClassConflict = TimetableClass::query()
            ->where('timetable_id', $timetable->id)
            ->where('day', $this->slotDay)
            ->where('start_time', '<', $this->slotEnd)
            ->where('end_time', '>', $this->slotStart)
            ->where('course_id', '!=', $this->slotCourseId)
            ->when($this->editingSlotId !== null, fn ($query) => $query->where('id', '!=', $this->editingSlotId))
            ->exists();
        if ($sameClassConflict) {
            $this->addError('slotStart', __('This timetable already has a different course in the selected time slot.'));
            return;
        }

        if ($course->teacher_id === null || (int) $course->teacher_id !== (int) $this->slotTeacherId) {
            $course->update(['teacher_id' => $this->slotTeacherId]);
        }

        $payload = [
            'timetable_id' => $timetable->id,
            'program_id' => $timetable->program_id,
            'course_id' => $this->slotCourseId,
            'teacher_id' => $this->slotTeacherId,
            'day' => $this->slotDay,
            'start_time' => $this->slotStart,
            'end_time' => $this->slotEnd,
            'venue' => $this->slotVenue,
        ];

        if ($this->editingSlotId !== null) {
            TimetableClass::query()->whereKey($this->editingSlotId)->update($payload);
            $this->collegeToast(__('Slot updated.'));
        } else {
            TimetableClass::query()->create($payload);
            $this->collegeToast(__('Slot added.'));
        }

        if ($exit) {
            $this->cancelSlotModal();
        } else {
            // Reset fields but keep Day
            $this->editingSlotId = null;
            $this->slotStart = '';
            $this->slotEnd = '';
            $this->slotCourseId = null;
            $this->slotTeacherId = null;
            $this->slotVenue = '';
            $this->slotConfirmTeacherReassign = false;
            $this->resetValidation();
        }

        $this->resetPage();
    }

    public function confirmDeleteSlot(int $slotId): void
    {
        $this->deletingSlotId = $slotId;
        $this->dispatch('open-modal', 'confirm-delete-slot-modal');
    }

    public function deleteSlot(): void
    {
        if ($this->deletingSlotId === null) {
            return;
        }
        $slot = TimetableClass::query()->with('timetable.academicSession')->findOrFail($this->deletingSlotId);
        if (!$slot->timetable->academicSession->is_current) {
            $this->deletingSlotId = null;
            $this->collegeToast(__('Historical timetables are read-only.'), 'error');
            return;
        }

        $slot->delete();
        $this->deletingSlotId = null;
        $this->collegeToast(__('Slot removed.'));
        $this->resetPage();
    }

    public function openBulkDuplicateModal(): void
    {
        if (empty($this->selectedIds)) {
            $this->collegeToast(__('Please select at least one timetable.'), 'error');
            return;
        }
        $currentSession = AcademicSession::query()->where('is_current', true)->first();
        $this->duplicateTargetSessionId = $currentSession?->id;
        $this->resetValidation();
        $this->dispatch('open-modal', 'bulk-duplicate-timetable-modal');
    }

    public function duplicateSelectedTimetables(): void
    {
        $this->validate([
            'duplicateTargetSessionId' => ['required', 'integer', 'exists:academic_sessions,id'],
        ]);

        if (empty($this->selectedIds)) {
            $this->collegeToast(__('No timetables selected.'), 'error');
            return;
        }

        $sources = Timetable::query()->with('classes')->whereIn('id', $this->selectedIds)->get();
        $duplicatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($sources, &$duplicatedCount, &$skippedCount): void {
            foreach ($sources as $source) {
                // Unique check
                $exists = Timetable::query()
                     ->where('program_id', $source->program_id)
                     ->where('level', $source->level)
                     ->where('session_id', $this->duplicateTargetSessionId)
                     ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $newTimetable = Timetable::query()->create([
                    'program_id' => $source->program_id,
                    'level' => $source->level,
                    'session_id' => $this->duplicateTargetSessionId,
                    'created_by' => Auth::id(),
                ]);

                foreach ($source->classes as $class) {
                    TimetableClass::query()->create([
                        'timetable_id' => $newTimetable->id,
                        'program_id' => $class->program_id,
                        'course_id' => $class->course_id,
                        'teacher_id' => $class->teacher_id,
                        'day' => $class->day,
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time,
                        'venue' => $class->venue,
                    ]);
                }
                $duplicatedCount++;
            }
        });

        $this->dispatch('close-modal', 'bulk-duplicate-timetable-modal');
        $this->selectedIds = [];

        if ($duplicatedCount > 0) {
            $this->collegeToast(__(':count timetable(s) duplicated successfully.', ['count' => $duplicatedCount]));
        }
        if ($skippedCount > 0) {
            $this->collegeToast(__(':count timetable(s) skipped because they already exist in target session.', ['count' => $skippedCount]), 'info');
        }

        $this->resetPage();
    }

    public function deleteSelectedTimetables(): void
    {
        if (empty($this->selectedIds)) {
            $this->collegeToast(__('Please select at least one timetable.'), 'error');
            return;
        }

        $timetables = Timetable::query()->with('academicSession')->whereIn('id', $this->selectedIds)->get();

        foreach ($timetables as $timetable) {
            if (!$timetable->academicSession->is_current) {
                $this->collegeToast(__('Deletion is only allowed for the current academic session.'), 'error');
                return;
            }
        }

        DB::transaction(function () use ($timetables): void {
            foreach ($timetables as $timetable) {
                $timetable->classes()->delete();
                $timetable->delete();
            }
        });

        $this->selectedIds = [];
        $this->collegeToast(__('Selected timetables deleted.'));
        $this->resetPage();
    }

    public function confirmDeleteTimetable(int $timetableId): void
    {
        $this->deletingTimetableId = $timetableId;
        $this->dispatch('open-modal', 'confirm-delete-timetable-modal');
    }

    public function deleteTimetable(): void
    {
        if ($this->deletingTimetableId === null) {
            return;
        }
        $timetable = Timetable::query()->with('academicSession')->findOrFail($this->deletingTimetableId);
        if (!$timetable->academicSession->is_current) {
            $this->deletingTimetableId = null;
            $this->collegeToast(__('Deletion is only allowed for the current academic session.'), 'error');
            return;
        }
        DB::transaction(function () use ($timetable): void {
            $timetable->classes()->delete();
            $timetable->delete();
        });
        $this->deletingTimetableId = null;
        if ($this->selectedTimetableId === $timetable->id) {
            $this->selectedTimetableId = null;
        }
        $this->collegeToast(__('Timetable deleted.'));
        $this->resetPage();
    }

    /**
     * @return EloquentCollection<int, Course>
     */
    public function coursesForTimetable(Timetable $timetable): EloquentCollection
    {
        $level = (int) ($timetable->level ?? 0);
        $yearLevel = match ($level) {
            100 => '1',
            200 => '2',
            300 => '3',
            400 => '4',
            default => null,
        };

        $q = Course::query()->where('program_id', $timetable->program_id);
        if ($yearLevel !== null) {
            $q->where('year_level', $yearLevel);
        }

        return $q->orderBy('code')->get();
    }

    public function render(): View
    {
        $timetables = Timetable::query()
            ->with(['program', 'academicSession', 'classes.course', 'classes.teacher.user'])
            ->when($this->filterProgramId !== null && $this->filterProgramId !== '', fn ($q) => $q->where('program_id', (int) $this->filterProgramId))
            ->when($this->filterLevel !== null && $this->filterLevel !== '', fn ($q) => $q->where('level', (int) $this->filterLevel))
            ->when($this->filterSessionId !== null && $this->filterSessionId !== '', fn ($q) => $q->where('session_id', (int) $this->filterSessionId))
            ->orderByDesc('id')
            ->paginate(15);

        $courseLists = [];
        foreach ($timetables as $tt) {
            $courseLists[$tt->id] = $this->coursesForTimetable($tt);
        }

        $programs = Program::query()->orderBy('name')->get();
        $sessions = AcademicSession::query()->orderByDesc('start_date')->get();
        $teachers = Teacher::query()->with('user')->orderBy('lastname')->orderBy('othernames')->get();

        return view('livewire.admin.academic.timetable-index', [
            'timetables' => $timetables,
            'courseLists' => $courseLists,
            'programs' => $programs,
            'sessions' => $sessions,
            'teachers' => $teachers,
            'weekDays' => self::weekDays(),
        ])->layout('components.layouts.admin', [
            'title' => __('Timetable'),
            'headerTitle' => __('Academic Timetable'),
            'headerDescription' => __('Setup weekly class schedule slots for programs, levels, and sessions.'),
        ]);
    }
}
