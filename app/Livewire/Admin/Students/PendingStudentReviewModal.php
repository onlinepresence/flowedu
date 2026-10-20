<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Actions\Students\AssertStudentApprovalAllowedByLicence;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class PendingStudentReviewModal extends Component
{
    use DispatchesCollegeToasts;

    public ?int $studentId = null;

    #[On('open-pending-student-review')]
    public function open(int $studentId): void
    {
        $student = Student::query()->findOrFail($studentId);

        abort_unless(auth()->user()?->hasAdminPermission('nav_students_index'), 403);

        $admin = auth()->user()?->admin;
        if ($admin !== null) {
            abort_unless($admin->canAccessStudent($student), 403);
        }

        $this->studentId = $student->id;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'pending-student-review-modal');
        $this->dispatch('pending-student-review-loaded', studentId: $student->id);
    }

    public function approve(AssertStudentApprovalAllowedByLicence $assertCap): void
    {
        if ($this->studentId === null) {
            return;
        }

        $student = Student::query()
            ->with(['program', 'parentGuardians'])
            ->findOrFail($this->studentId);

        $admin = auth()->user()?->admin;
        if ($admin !== null) {
            abort_unless($admin->canAccessStudent($student), 403);
        }

        if ($student->approved) {
            $this->addError('approve', __('Student has already been approved.'));

            return;
        }

        if ($student->parentGuardians->isEmpty()) {
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

        $student->forceFill([
            'approved' => true,
            'admission_index' => $student->index_number,
            'department_id' => $student->department_id ?? $student->program?->department_id,
        ])->save();

        $this->dispatch('close-modal', 'pending-student-review-modal');
        $this->studentId = null;
        $this->dispatch('pending-student-approved', studentId: $student->id);
        $this->collegeToast(__('Student :name (:index) has been approved.', [
            'name' => $student->lastname,
            'index' => $student->index_number,
        ]));
    }

    public function render(): View
    {
        $student = $this->studentId !== null
            ? Student::query()
                ->with(['user', 'program.department.faculty', 'department', 'hall', 'parentGuardians'])
                ->find($this->studentId)
            : null;

        return view('livewire.admin.students.pending-student-review-modal', [
            'student' => $student,
            'photoDataUrl' => $student !== null ? $this->photoDataUrl($student) : null,
            'guardianComplete' => $student?->parentGuardians->isNotEmpty() ?? false,
        ]);
    }

    private function photoDataUrl(Student $student): ?string
    {
        $relativePath = (string) $student->profile_pic;
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $disk = Storage::disk('college_uploads');
        if (! $disk->exists($relativePath)) {
            return null;
        }

        $full = $disk->path($relativePath);
        if (! is_readable($full) || filesize($full) > 1_500_000) {
            return null;
        }

        $mime = @mime_content_type($full) ?: 'image/jpeg';
        $raw = @file_get_contents($full);
        if ($raw === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
