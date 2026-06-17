<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\TimetableClass;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class TeacherTimetablePage extends Component
{
    /** @var list<string> */
    private const DAY_ORDER = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /**
     * @return Collection<string, Collection<int, TimetableClass>>
     */
    private function slotsGroupedByDay(Collection $slots): Collection
    {
        $normalized = $slots->groupBy(function (TimetableClass $slot): string {
            $day = trim((string) ($slot->day ?? ''));
            if ($day === '') {
                return __('Other');
            }

            return ucfirst(strtolower($day));
        });

        $ordered = collect();
        foreach (self::DAY_ORDER as $day) {
            if ($normalized->has($day)) {
                $ordered->put($day, $normalized->get($day)->sortBy(fn (TimetableClass $s) => (string) ($s->start_time ?? ''))->values());
            }
        }

        foreach ($normalized->keys() as $day) {
            if ($day !== '' && ! $ordered->has($day)) {
                $ordered->put($day, $normalized->get($day)->sortBy(fn (TimetableClass $s) => (string) ($s->start_time ?? ''))->values());
            }
        }

        return $ordered;
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        $slots = $teacher
            ? TimetableClass::query()
                ->where('teacher_id', $teacher->id)
                ->with(['course', 'program'])
                ->orderBy('day')
                ->orderBy('start_time')
                ->get()
            : collect();

        $slotsByDay = $this->slotsGroupedByDay($slots);

        return view('livewire.teacher.teacher-timetable-page', [
            'slotsByDay' => $slotsByDay,
            'hasSlots' => $slots->isNotEmpty(),
        ])->layout('components.layouts.teacher', [
            'title' => __('Class timetable'),
            'headerDescription' => __('Your scheduled classes for the current academic session.'),
        ]);
    }
}
