<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Actions\Students\AssertStudentApprovalAllowedByLicence;
use App\Models\Student;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ApproveStudentPage extends Component
{
    public string $index_number = '';

    public bool $guardianComplete = false;

    public int $userId = 0;

    public ?Student $student = null;

    public function mount(string $index_number, string $guardian, string $id): void
    {
        $this->index_number = $index_number;
        $this->guardianComplete = ((int) $guardian) === 1;
        $this->userId = (int) $id;

        $this->student = Student::query()
            ->where('user_id', $this->userId)
            ->with(['user', 'program'])
            ->firstOrFail();

        if ($this->student->index_number !== $this->index_number) {
            abort(404);
        }
    }

    public function approve(AssertStudentApprovalAllowedByLicence $assertCap): void
    {
        if ($this->student === null) {
            return;
        }

        if ($this->student->approved) {
            $this->addError('approve', __('Student has already been approved.'));

            return;
        }

        if (! $this->guardianComplete) {
            $this->addError('approve', __('Student cannot be approved. Guardian information not completed.'));

            return;
        }

        try {
            $assertCap();
        } catch (ValidationException $e) {
            $msg = $e->validator->errors()->first('system_message')
                ?: $e->validator->errors()->first()
                ?: __('Approval blocked by licence cap.');
            $this->addError('approve', $msg);

            return;
        }

        $departmentId = $this->student->department_id ?? $this->student->program?->department_id;

        $this->student->forceFill([
            'approved' => true,
            'admission_index' => $this->index_number,
            'department_id' => $departmentId,
        ])->save();

        CollegeFlash::forNextRequestToo('status', __('Student :name (:index) has been approved.', [
            'name' => $this->student->lastname,
            'index' => $this->index_number,
        ]));

        $this->redirect(route('admin.students.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.students.approve-student-page')
            ->layout('components.layouts.admin', ['title' => __('Approve student')]);
    }
}
