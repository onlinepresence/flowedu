<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Student;
use App\Models\DisciplinaryRecord;
use App\Models\Activity;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentShowPage extends Component
{
    use DispatchesCollegeToasts;

    public Student $student;

    /** @var \Illuminate\Database\Eloquent\Collection */
    public $disciplinaryRecords;

    /** @var \Illuminate\Database\Eloquent\Collection */
    public $activities;

    public string $activityName = '';
    public string $activityRole = '';
    public string $activityDate = '';
    public bool $showActivityForm = false;

    public function mount(string $index_number): void
    {
        $this->student = Student::query()
            ->where('index_number', $index_number)
            ->with(['user', 'program.department', 'department', 'hall', 'medicalHistory', 'parentGuardians', 'clearances'])
            ->firstOrFail();

        $this->disciplinaryRecords = DisciplinaryRecord::query()
            ->where('index_number', $this->student->index_number)
            ->orderByDesc('date_of_action')
            ->get();

        $this->activities = Activity::query()
            ->where('student_id', $this->student->id)
            ->orderByDesc('participation_date')
            ->get();
    }

    public function toggleActivityForm(): void
    {
        $this->showActivityForm = !$this->showActivityForm;
        $this->reset(['activityName', 'activityRole', 'activityDate']);
    }

    public function addActivity(): void
    {
        $this->validate([
            'activityName' => ['required', 'string', 'max:255'],
            'activityRole' => ['required', 'string', 'max:255'],
            'activityDate' => ['required', 'date'],
        ]);

        Activity::query()->create([
            'student_id' => $this->student->id,
            'activity_name' => $this->activityName,
            'role' => $this->activityRole,
            'participation_date' => $this->activityDate,
        ]);

        $this->reset(['activityName', 'activityRole', 'activityDate', 'showActivityForm']);
        
        $this->activities = Activity::query()
            ->where('student_id', $this->student->id)
            ->orderByDesc('participation_date')
            ->get();

        $this->collegeToast(__('Activity recorded successfully.'));
    }

    public function deleteActivity(int $id): void
    {
        Activity::query()
            ->where('student_id', $this->student->id)
            ->where('id', $id)
            ->delete();

        $this->activities = Activity::query()
            ->where('student_id', $this->student->id)
            ->orderByDesc('participation_date')
            ->get();

        $this->collegeToast(__('Activity deleted successfully.'));
    }

    public function render(): View
    {
        return view('livewire.admin.students.student-show-page')
            ->layout('components.layouts.admin', [
                'title' => __('Student Profile - :name', ['name' => $this->student->lastname]),
                'headerTitle' => __('Student Profile'),
                'headerDescription' => __('Detailed profile information for :name (:index).', [
                    'name' => trim(implode(' ', array_filter([$this->student->firstname, $this->student->lastname]))),
                    'index' => $this->student->index_number,
                ]),
            ]);
    }
}
