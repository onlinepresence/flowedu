<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Legacy create_student / update_student persistence (students + users.username).
 */
final class SaveStudentAdmissionProfileAction
{
    public function __construct(
        private readonly StoreStudentProfilePhotoAction $storePhoto
    ) {}

    /**
     * @param  array<string, mixed>  $validated  validated scalar fields only
     */
    public function create(
        User $user,
        array $validated,
        TemporaryUploadedFile|UploadedFile $profilePic
    ): Student {
        $relativePath = $this->storePhoto->execute($profilePic, null);

        $student = new Student;
        $student->forceFill([
            'user_id' => $user->id,
            'index_number' => $validated['index_number'],
            'admission_index' => $validated['index_number'],
            'lastname' => $validated['lastname'],
            'firstname' => $validated['firstname'],
            'othernames' => $validated['othernames'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'nationality' => $validated['nationality'],
            'insurance_number' => $validated['insurance_number'] ?? null,
            'ghana_card' => $validated['ghana_card'],
            'contact_address' => $validated['contact_address'],
            'phone_number' => $validated['phone_number'],
            'religion' => $validated['religion'] ?? null,
            'denomination' => $validated['denomination'] ?? null,
            'disability_status' => $validated['disability_status'] ?? 'no',
            'disability_type' => $validated['disability_type'] ?? null,
            'program_id' => $validated['program_id'],
            'hall_id' => $validated['hall_id'],
            'gender' => $validated['gender'],
            'blood_group' => $validated['blood_group'] ?? null,
            'profile_pic' => $relativePath,
        ])->save();

        $user->forceFill(['username' => $validated['username']])->save();

        return $student->fresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Student $student, User $user, array $validated, TemporaryUploadedFile|UploadedFile|null $profilePic): void
    {
        $prevPic = $student->profile_pic;
        $profilePath = $prevPic;

        if ($profilePic !== null) {
            $profilePath = $this->storePhoto->execute($profilePic, $prevPic);
        }

        $bloodGroupVal = $student->blood_group;
        if (is_null($bloodGroupVal) && array_key_exists('blood_group', $validated)) {
            $bloodGroupVal = $validated['blood_group'];
        }

        $student->forceFill([
            'index_number' => $validated['index_number'],
            'lastname' => $validated['lastname'],
            'firstname' => $validated['firstname'],
            'othernames' => $validated['othernames'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'nationality' => $validated['nationality'],
            'insurance_number' => $validated['insurance_number'] ?? null,
            'ghana_card' => $validated['ghana_card'],
            'contact_address' => $validated['contact_address'],
            'phone_number' => $validated['phone_number'],
            'religion' => $validated['religion'] ?? null,
            'denomination' => $validated['denomination'] ?? null,
            'disability_status' => $validated['disability_status'] ?? 'no',
            'disability_type' => $validated['disability_type'] ?? null,
            'blood_group' => $bloodGroupVal,
            'account_bank' => $validated['account_bank'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'ssnit_number' => $validated['ssnit_number'] ?? null,
            'profile_pic' => $profilePath,
        ])->save();

        $user->forceFill(['username' => $validated['index_number']])->save();
    }
}
