<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Actions\Students\SaveParentGuardianAction;
use App\Http\Requests\Student\SaveParentGuardianRequest;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentSetupGuardianPage extends Component
{
    use DispatchesCollegeToasts;

    public int $student_id = 0;

    public int $guardian_id = 0;

    public string $name = '';

    public string $relationship = '';

    public string $phone_number = '';

    public string $address = '';

    public string $email = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null) {
            $this->redirect(route('student.setup.personal'));

            return;
        }

        $this->student_id = $student->id;

        // Prefill first guardian if exists
        $firstGuardian = $student->parentGuardians()->first();
        if ($firstGuardian) {
            $this->edit($firstGuardian->id);
        }
    }

    public function startNew(): void
    {
        $this->guardian_id = 0;
        $this->name = '';
        $this->relationship = '';
        $this->phone_number = '';
        $this->address = '';
        $this->email = '';
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        /** @var Student $student */
        $student = auth()->user()->student;
        $row = $student->parentGuardians()->whereKey($id)->firstOrFail();
        $this->guardian_id = $row->id;
        $this->name = $row->name;
        $this->relationship = $row->relationship;
        $this->phone_number = $row->phone_number;
        $this->address = (string) ($row->address ?? '');
        $this->email = (string) ($row->email ?? '');
        $this->resetErrorBag();
    }

    public function save(SaveParentGuardianAction $action): void
    {
        /** @var Student $student */
        $student = auth()->user()->student;
        if ($student === null) {
            return;
        }

        $validated = $this->validate(SaveParentGuardianRequest::rulesForStudent($student->id));
        $gid = (int) ($validated['guardian_id'] ?? 0) > 0 ? (int) $validated['guardian_id'] : null;
        unset($validated['guardian_id'], $validated['student_id']);

        $wasEdit = $gid !== null;
        $action->execute($student, $validated, $gid);
        $this->startNew();
        $this->collegeToast($wasEdit ? __('Changes have been saved') : __('Guardian information has been added'));
    }

    public function delete(int $id): void
    {
        /** @var Student $student */
        $student = auth()->user()->student;
        if ($student === null) {
            return;
        }

        $row = $student->parentGuardians()->whereKey($id)->first();
        if ($row) {
            $row->delete();
            $this->collegeToast(__('Guardian removed successfully.'));
            if ($this->guardian_id === $id) {
                $this->startNew();
            }
        }
    }

    public function render(): View
    {
        /** @var Student|null $student */
        $student = auth()->user()->student;

        return view('livewire.student.student-setup-guardian-page', [
            'guardians' => $student?->parentGuardians()->orderBy('name')->get() ?? collect(),
        ])->layout('components.layouts.student', ['title' => __('Guardian information')]);
    }
}
