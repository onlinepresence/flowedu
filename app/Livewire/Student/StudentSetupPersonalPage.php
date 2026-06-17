<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Actions\Students\SaveStudentAdmissionProfileAction;
use App\Http\Requests\Student\StoreStudentAdmissionRequest;
use App\Http\Requests\Student\UpdateStudentAdmissionRequest;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class StudentSetupPersonalPage extends Component
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

    public ?int $program_id = null;

    public ?int $hall_id = null;

    public string $username = '';

    public string $gender = '';

    public ?string $blood_group = null;

    public ?string $profilePicPond = null;

    public string $account_bank = '';

    public string $account_number = '';

    public string $ssnit_number = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->user_id = $user->id;

        $student = $user->student;

        if ($student !== null) {
            if ($student->approved && $student->is_new) {
                $this->redirect(route('student.setup.status'));

                return;
            }

            if ($student->approved && ! $student->is_new) {
                $this->redirect(route('student.dashboard'));

                return;
            }

            $this->fillFromStudent($student);
        }
    }

    public function save(SaveStudentAdmissionProfileAction $action): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        if ($student === null) {
            $hadUsername = $user->username !== null && $user->username !== '';
            $validated = $this->validate($this->createRules($user->id));
            $pic = $this->uploadedFromPending($this->profilePicPond, true);
            if ($pic === null) {
                return;
            }
            $action->create($user, $validated, $pic);
            $this->clearPending($this->profilePicPond);
            $this->profilePicPond = null;
            $this->collegeToast(! $hadUsername ? __('Your account details have been saved') : __('Changes have been applied'));

            return;
        }

        $validated = $this->validate($this->updateRules($student));
        $file = $this->uploadedFromPending($this->profilePicPond, false);
        $action->update($student, $user, $validated, $file);
        if ($this->profilePicPond !== null && $this->profilePicPond !== '') {
            $this->clearPending($this->profilePicPond);
            $this->profilePicPond = null;
        }
        $this->collegeToast(__('Changes have been applied'));
    }

    /**
     * @return array<string, mixed>
     */
    private function createRules(int $userId): array
    {
        $rules = StoreStudentAdmissionRequest::rulesForUser($userId);
        unset($rules['profile_pic']);
        $rules['profilePicPond'] = ['required', 'string', 'max:500'];

        return $rules;
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

    private function uploadedFromPending(?string $pendingPath, bool $required): ?UploadedFile
    {
        if ($pendingPath === null || $pendingPath === '') {
            if ($required) {
                $this->addError('profilePicPond', __('Passport photo is required.'));
            }

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
        $this->program_id = $student->program_id;
        $this->hall_id = $student->hall_id;
        $this->username = (string) ($student->user?->username ?? '');
        $this->gender = $student->gender;
        $this->blood_group = $student->blood_group;
        $this->account_bank = (string) ($student->account_bank ?? '');
        $this->account_number = (string) ($student->account_number ?? '');
        $this->ssnit_number = (string) ($student->ssnit_number ?? '');
    }

    public function render(): View
    {
        return view('livewire.student.student-setup-personal-page', [
            'programs' => Program::query()->with('department')->orderBy('name')->get(),
            'halls' => Hall::query()->orderBy('name')->get(),
            'hasStudent' => auth()->user()?->student !== null,
        ])->layout('components.layouts.student', ['title' => __('Personal information')]);
    }
}
