<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProgramClassesPage extends Component
{
    public Program $program;

    public function mount(int $program_id): void
    {
        $this->program = Program::query()->findOrFail($program_id);
    }

    public function render(): View
    {
        $courses = Course::query()
            ->where('program_id', $this->program->id)
            ->with('teacher.user')
            ->orderBy('year_level')
            ->orderBy('code')
            ->get()
            ->groupBy('year_level');

        $levels = [];
        $maxLevel = max(1, (int) $this->program->program_length);
        for ($i = 1; $i <= $maxLevel; $i++) {
            $levels[] = [
                'year' => $i,
                'label' => $i * 100,
                'count' => ($courses->get((string) $i) ?? collect())->count(),
            ];
        }

        return view('livewire.admin.academic.program-classes-page', [
            'coursesByLevel' => $courses,
            'levels' => $levels,
        ])->layout('components.layouts.admin', [
            'title' => __('Program classes'),
            'headerTitle' => $this->program->name,
            'headerDescription' => __('Manage class levels and assigned courses for this program.'),
        ]);
    }
}
