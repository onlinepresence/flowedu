<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Http\Requests\Teacher\UpdateTeacherProfileRequest;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

final class TeacherProfilePage extends Component
{
    use DispatchesCollegeToasts;

    public int $user_id = 0;

    public string $lastname = '';

    public string $othernames = '';

    public ?string $title = null;

    public string $gender = '';

    public string $date_of_birth = '';

    public string $nationality = '';

    public string $ghana_card = '';

    public string $contact_address = '';

    public string $phone_number = '';

    public string $staff_id = '';

    public ?int $department_id = null;

    public ?string $office_location = null;

    public ?string $office_hours = null;

    public ?string $rank = null;

    public ?string $qualification = null;

    public string $specialization = '';

    public ?string $orcid_id = null;

    public ?string $google_scholar_url = null;

    public ?string $employment_type = null;

    public int $years_experience = 0;

    public string $date_of_appointment = '';

    public string $emergency_name = '';

    public string $emergency_phone = '';

    public string $research_interests = '';

    public ?string $profilePicPond = null;

    public ?string $cvPond = null;

    public ?string $certificatePond = null;

    public ?string $idDocumentPond = null;

    public bool $staffIdLocked = false;

    public ?Teacher $teacher = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing('teacher.department');
        $teacher = $user->teacher;
        if ($teacher === null) {
            abort(403);
        }

        $this->teacher = $teacher;
        $this->user_id = $user->id;
        $this->staffIdLocked = $teacher->staff_id !== null && $teacher->staff_id !== '';

        $this->lastname = (string) ($teacher->lastname ?? '');
        $this->othernames = (string) ($teacher->othernames ?? '');
        $this->title = $teacher->title;
        $this->gender = (string) ($teacher->gender ?? '');
        $this->date_of_birth = $teacher->date_of_birth?->format('Y-m-d') ?? '';
        $this->nationality = (string) ($teacher->nationality ?? '');
        $this->ghana_card = (string) ($teacher->ghana_card ?? '');
        $this->contact_address = (string) ($teacher->contact_address ?? '');
        $this->phone_number = (string) ($teacher->phone_number ?? '');
        $this->staff_id = (string) ($teacher->staff_id ?? '');
        $this->department_id = $teacher->department_id;
        $this->office_location = $teacher->office_location;
        $this->office_hours = $teacher->office_hours;
        $rankKeys = array_keys(UpdateTeacherProfileRequest::rankOptions());
        $this->rank = $teacher->rank !== null && $teacher->rank !== '' && in_array((string) $teacher->rank, $rankKeys, true)
            ? (string) $teacher->rank
            : null;
        $qualKeys = array_keys(UpdateTeacherProfileRequest::qualificationOptions());
        $this->qualification = $teacher->qualification !== null && $teacher->qualification !== '' && in_array((string) $teacher->qualification, $qualKeys, true)
            ? (string) $teacher->qualification
            : null;
        $this->specialization = (string) ($teacher->specialization ?? '');
        $this->orcid_id = $teacher->orcid_id;
        $this->google_scholar_url = $teacher->google_scholar_url;
        $allowedEmployment = ['Full-time', 'Part-time', 'Visiting'];
        $this->employment_type = $teacher->employment_type !== null && $teacher->employment_type !== '' && in_array((string) $teacher->employment_type, $allowedEmployment, true)
            ? (string) $teacher->employment_type
            : 'Full-time';
        $this->years_experience = (int) ($teacher->years_experience ?? 0);
        $this->date_of_appointment = $teacher->date_of_appointment?->format('Y-m-d') ?? '';
        $this->emergency_name = (string) ($teacher->emergency_name ?? '');
        $this->emergency_phone = (string) ($teacher->emergency_phone ?? '');
        $this->research_interests = (string) ($teacher->research_interests ?? '');
    }

    public function save(): void
    {
        if ($this->teacher === null) {
            return;
        }

        $this->normalizeOptionalFields();
        $this->normalizeDepartmentId();

        /** @var User $user */
        $user = auth()->user();
        $teacher = $this->teacher->fresh() ?? $this->teacher;
        $prevStaffId = $teacher->staff_id;
        $prevUsername = $user->username;

        $rules = array_merge(
            UpdateTeacherProfileRequest::rulesForTeacher($teacher),
            [
                'cvPond' => ['nullable', 'string', 'max:500'],
                'certificatePond' => ['nullable', 'string', 'max:500'],
                'idDocumentPond' => ['nullable', 'string', 'max:500'],
            ]
        );

        $validated = $this->validate($rules);

        $teacherData = [
            'lastname' => $validated['lastname'],
            'othernames' => $validated['othernames'],
            'title' => $validated['title'] !== '' ? $validated['title'] : null,
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'nationality' => $validated['nationality'],
            'ghana_card' => $validated['ghana_card'],
            'contact_address' => $validated['contact_address'],
            'phone_number' => $validated['phone_number'],
            'staff_id' => $validated['staff_id'] !== '' ? $validated['staff_id'] : null,
            'department_id' => $validated['department_id'] ?? null,
            'office_location' => $validated['office_location'] !== '' ? $validated['office_location'] : null,
            'office_hours' => $validated['office_hours'] !== '' ? $validated['office_hours'] : null,
            'rank' => $validated['rank'] ?? null,
            'qualification' => $validated['qualification'] ?? null,
            'specialization' => $validated['specialization'],
            'orcid_id' => $validated['orcid_id'] !== '' ? $validated['orcid_id'] : null,
            'google_scholar_url' => $validated['google_scholar_url'] !== '' ? $validated['google_scholar_url'] : null,
            'employment_type' => $validated['employment_type'] ?? null,
            'years_experience' => (int) $validated['years_experience'],
            'emergency_name' => $validated['emergency_name'] !== '' ? $validated['emergency_name'] : null,
            'emergency_phone' => $validated['emergency_phone'] !== '' ? $validated['emergency_phone'] : null,
            'research_interests' => $validated['research_interests'] !== '' ? $validated['research_interests'] : null,
            'date_of_appointment' => $validated['date_of_appointment'],
            'is_onboarded' => true,
        ];

        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        $cv = $this->uploadedFromPending($this->cvPond, 'cvPond');
        if ($this->pendingFailed($this->cvPond, $cv)) {
            return;
        }
        if ($cv !== null) {
            $this->deleteIfSafe($teacher->cv);
            $teacherData['cv'] = $cv->store('teachers/cv', 'college_uploads');
            $this->clearPending($this->cvPond);
            $this->cvPond = null;
        }

        $certificate = $this->uploadedFromPending($this->certificatePond, 'certificatePond');
        if ($this->pendingFailed($this->certificatePond, $certificate)) {
            return;
        }
        if ($certificate !== null) {
            $this->deleteIfSafe($teacher->certificate);
            $teacherData['certificate'] = $certificate->store('teachers/certificates', 'college_uploads');
            $this->clearPending($this->certificatePond);
            $this->certificatePond = null;
        }

        $idDoc = $this->uploadedFromPending($this->idDocumentPond, 'idDocumentPond');
        if ($this->pendingFailed($this->idDocumentPond, $idDoc)) {
            return;
        }
        if ($idDoc !== null) {
            $this->deleteIfSafe($teacher->id_document);
            $teacherData['id_document'] = $idDoc->store('teachers/id-documents', 'college_uploads');
            $this->clearPending($this->idDocumentPond);
            $this->idDocumentPond = null;
        }

        $teacher->forceFill($teacherData)->save();

        $newStaffId = $teacher->staff_id;
        if (
            ($prevStaffId === null || $prevStaffId === '')
            && $newStaffId !== null && $newStaffId !== ''
            && ($prevUsername === null || $prevUsername === '')
        ) {
            $user->forceFill(['username' => $newStaffId])->save();
        }

        $this->teacher = $teacher->fresh(['department']);
        $this->staffIdLocked = $this->teacher->staff_id !== null && $this->teacher->staff_id !== '';

        $this->collegeToast(__('Your profile details have been updated successfully.'));
    }

    public function saveProfilePicture(): void
    {
        $this->validate([
            'profilePicPond' => ['required', 'string', 'max:500'],
        ]);

        if ($this->teacher === null) {
            return;
        }

        $teacher = $this->teacher->fresh() ?? $this->teacher;
        $profilePic = $this->uploadedFromPending($this->profilePicPond, 'profilePicPond');
        if ($profilePic === null) {
            return;
        }

        $this->deleteIfSafe($teacher->profile_pic);
        $storedPath = $profilePic->store('teachers/profiles', 'college_uploads');

        $teacher->forceFill([
            'profile_pic' => $storedPath,
        ])->save();

        $this->clearPending($this->profilePicPond);
        $this->profilePicPond = null;

        $this->teacher = $teacher->fresh(['department']);
        $this->collegeToast(__('Profile picture updated successfully.'));
    }

    public function render(): View
    {
        return view('livewire.teacher.teacher-profile-page', [
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'rankOptions' => UpdateTeacherProfileRequest::rankOptions(),
            'qualificationOptions' => UpdateTeacherProfileRequest::qualificationOptions(),
        ])->layout('components.layouts.teacher', ['title' => __('My profile')]);
    }

    private function normalizeOptionalFields(): void
    {
        if ($this->title === '') {
            $this->title = null;
        }
        if ($this->office_location === '') {
            $this->office_location = null;
        }
        if ($this->office_hours === '') {
            $this->office_hours = null;
        }
        if ($this->rank === '') {
            $this->rank = null;
        }
        if ($this->qualification === '') {
            $this->qualification = null;
        }
        if ($this->orcid_id === '') {
            $this->orcid_id = null;
        }
        if ($this->google_scholar_url === '') {
            $this->google_scholar_url = null;
        }
        if ($this->employment_type === '') {
            $this->employment_type = null;
        }
    }

    private function normalizeDepartmentId(): void
    {
        if ($this->department_id !== null) {
            $this->department_id = (int) $this->department_id;
        }
    }

    private function pendingFailed(?string $pendingPath, ?UploadedFile $file): bool
    {
        return $pendingPath !== null && $pendingPath !== '' && $file === null;
    }

    private function deleteIfSafe(?string $path): void
    {
        if ($path !== null && $path !== '' && ! str_contains($path, '..')) {
            Storage::disk('college_uploads')->delete($path);
        }
    }

    private function uploadedFromPending(?string $pendingPath, string $errorField): ?UploadedFile
    {
        if ($pendingPath === null || $pendingPath === '') {
            return null;
        }

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($pendingPath, $userId)) {
            $this->addError($errorField, __('Uploaded file is invalid.'));

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
}
