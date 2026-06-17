<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Actions\Students\SaveParentGuardianAction;
use App\Actions\Students\SaveStudentAdmissionProfileAction;
use App\Http\Requests\Student\UpdateStudentAdmissionRequest;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Student;
use App\Models\User;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Legacy update_student + change_picture for enrolled students (student.profile).
 */
class StudentProfilePage extends Component
{
    use DispatchesCollegeToasts;

    public int $user_id = 0;

    public string $index_number = '';

    public string $lastname = '';

    public string $firstname = '';

    public string $othernames = '';

    public string $date_of_birth = '';

    public string $nationality = '';

    public string $insurance_number = '';

    public string $ghana_card = '';

    public string $contact_address = '';

    public string $phone_number = '';

    public string $religion = '';

    public string $denomination = '';

    public string $disability_status = 'no';

    public string $disability_type = '';

    public string $gender = '';

    public ?string $blood_group = null;

    public ?string $profilePicPond = null;

    public string $account_bank = '';

    public string $account_number = '';

    public string $ssnit_number = '';

    // Guardian Properties
    public int $guardian_id = 0;

    public string $guardian_name = '';

    public string $guardian_relationship = '';

    public string $guardian_phone_number = '';

    public string $guardian_address = '';

    public string $guardian_email = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->user_id = $user->id;
        $student = $user->student;
        if ($student === null) {
            abort(403);
        }

        $this->fillFromStudent($student);

        // Prefill first guardian if exists
        $firstGuardian = $student->parentGuardians()->first();
        if ($firstGuardian) {
            $this->editGuardian($firstGuardian->id);
        }
    }

    public function save(SaveStudentAdmissionProfileAction $action): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null) {
            return;
        }

        $validated = $this->validate($this->updateRules($student));
        $file = $this->uploadedFromPending($this->profilePicPond);
        $action->update($student, $user, $validated, $file);
        if ($this->profilePicPond !== null && $this->profilePicPond !== '') {
            $this->clearPending($this->profilePicPond);
            $this->profilePicPond = null;
        }
        $this->collegeToast(__('Changes have been applied'));
    }

    public function saveProfilePicture(\App\Actions\Students\StoreStudentProfilePhotoAction $storePhoto): void
    {
        $this->validate([
            'profilePicPond' => ['required', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null) {
            return;
        }

        $file = $this->uploadedFromPending($this->profilePicPond);
        if ($file === null) {
            return;
        }

        $prevPic = $student->profile_pic;
        $profilePath = $storePhoto->execute($file, $prevPic);
        $student->forceFill([
            'profile_pic' => $profilePath,
        ])->save();

        if ($this->profilePicPond !== null && $this->profilePicPond !== '') {
            $this->clearPending($this->profilePicPond);
            $this->profilePicPond = null;
        }

        $this->collegeToast(__('Profile picture updated successfully.'));
    }

    public function startNewGuardian(): void
    {
        $this->guardian_id = 0;
        $this->guardian_name = '';
        $this->guardian_relationship = '';
        $this->guardian_phone_number = '';
        $this->guardian_address = '';
        $this->guardian_email = '';
        $this->resetErrorBag();
    }

    public function editGuardian(int $id): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null) {
            return;
        }

        $row = $student->parentGuardians()->whereKey($id)->firstOrFail();
        $this->guardian_id = $row->id;
        $this->guardian_name = $row->name;
        $this->guardian_relationship = $row->relationship;
        $this->guardian_phone_number = $row->phone_number;
        $this->guardian_address = (string) ($row->address ?? '');
        $this->guardian_email = (string) ($row->email ?? '');
        $this->resetErrorBag();
    }

    public function saveGuardian(SaveParentGuardianAction $action): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null) {
            return;
        }

        $validated = $this->validate([
            'guardian_id' => ['nullable', 'integer', 'min:0'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relationship' => ['required', 'string', 'max:100'],
            'guardian_phone_number' => ['required', 'string', new \App\Rules\GhanaMobilePhone],
            'guardian_address' => ['nullable', 'string', 'max:2000'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
        ], [], [
            'guardian_name' => __('name'),
            'guardian_relationship' => __('relationship'),
            'guardian_phone_number' => __('phone number'),
            'guardian_address' => __('address'),
            'guardian_email' => __('email'),
        ]);

        $data = [
            'name' => $validated['guardian_name'],
            'relationship' => $validated['guardian_relationship'],
            'phone_number' => $validated['guardian_phone_number'],
            'address' => $validated['guardian_address'] ?? null,
            'email' => $validated['guardian_email'] ?? null,
        ];

        $gid = $this->guardian_id > 0 ? $this->guardian_id : null;
        $wasEdit = $gid !== null;

        $row = $action->execute($student, $data, $gid);
        $this->guardian_id = $row->id;
        $this->guardian_name = $row->name;
        $this->guardian_relationship = $row->relationship;
        $this->guardian_phone_number = $row->phone_number;
        $this->guardian_address = (string) ($row->address ?? '');
        $this->guardian_email = (string) ($row->email ?? '');
        $this->resetErrorBag();
        $this->collegeToast($wasEdit ? __('Guardian information updated') : __('Guardian information added'));
    }

    public function deleteGuardian(int $id): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null) {
            return;
        }

        $row = $student->parentGuardians()->whereKey($id)->first();
        if ($row) {
            $row->delete();
            $this->collegeToast(__('Guardian deleted successfully.'));
            if ($this->guardian_id === $id) {
                $this->startNewGuardian();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function updateRules(Student $student): array
    {
        $rules = UpdateStudentAdmissionRequest::rulesForStudent($student);
        unset($rules['profile_pic']);
        $rules['profilePicPond'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    private function uploadedFromPending(?string $pendingPath): ?UploadedFile
    {
        if ($pendingPath === null || $pendingPath === '') {
            return null;
        }

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($pendingPath, $userId)) {
            $this->addError('profilePicPond', __('Uploaded photo is invalid.'));

            return null;
        }

        $path = Storage::disk('local')->path($pendingPath);

        return new UploadedFile(
            $path,
            basename($path),
            mime_content_type($path) ?: null,
            null,
            true
        );
    }

    private function clearPending(?string $pendingPath): void
    {
        if ($pendingPath !== null && $pendingPath !== '') {
            Storage::disk('local')->delete($pendingPath);
        }
    }

    private function fillFromStudent(Student $student): void
    {
        $student->loadMissing('user');
        $this->index_number = $student->index_number;
        $this->lastname = $student->lastname;
        $this->firstname = (string) ($student->firstname ?? '');
        $this->othernames = (string) ($student->othernames ?? '');
        $this->date_of_birth = $student->date_of_birth?->format('Y-m-d') ?? '';
        $this->nationality = $student->nationality;
        $this->insurance_number = (string) ($student->insurance_number ?? '');
        $this->ghana_card = (string) ($student->ghana_card ?? '');
        $this->contact_address = $student->contact_address;
        $this->phone_number = $student->phone_number;
        $this->religion = (string) ($student->religion ?? '');
        $this->denomination = (string) ($student->denomination ?? '');
        $this->disability_status = $student->disability_status ?? 'no';
        $this->disability_type = (string) ($student->disability_type ?? '');
        $this->gender = $student->gender;
        $this->blood_group = $student->blood_group;
        $this->account_bank = (string) ($student->account_bank ?? '');
        $this->account_number = (string) ($student->account_number ?? '');
        $this->ssnit_number = (string) ($student->ssnit_number ?? '');
    }

    public function getPhotoDataUrl(): ?string
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;
        if ($student === null || !$student->profile_pic) {
            return null;
        }

        $relativePath = $student->profile_pic;
        if (str_contains($relativePath, '..')) {
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

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        return view('livewire.student.student-profile-page', [
            'guardians' => $student?->parentGuardians()->orderBy('name')->get() ?? collect(),
            'photoDataUrl' => $this->getPhotoDataUrl(),
        ])->layout('components.layouts.student', [
            'title' => __('My profile'),
            'headerDescription' => __('Manage your personal, guardian, and academic details here.'),
        ]);
    }
}
