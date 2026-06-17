<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Rules\GhanaCardNumber;
use App\Rules\GhanaMobilePhone;
use App\Rules\PassportPhotoFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Legacy submit create_student validation (student/submit.php).
 */
class StoreStudentAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = (int) auth()->id();

        return self::rulesForUser($userId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesForUser(int $userId): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::in([$userId])],
            'index_number' => ['required', 'string', 'max:64'],
            'lastname' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'othernames' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:100'],
            'insurance_number' => ['nullable', 'numeric'],
            'ghana_card' => ['required', 'string', new GhanaCardNumber, Rule::unique('students', 'ghana_card')],
            'contact_address' => ['required', 'string', 'max:2000'],
            'phone_number' => ['required', 'string', new GhanaMobilePhone, Rule::unique('students', 'phone_number')],
            'religion' => ['nullable', 'string', 'max:255'],
            'denomination' => ['nullable', 'string', 'max:100'],
            'disability_status' => ['nullable', 'string', Rule::in(['no', 'yes'])],
            'disability_type' => ['nullable', 'required_if:disability_status,yes', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'profile_pic' => ['required', 'file', 'mimes:jpg,jpeg,png,avif,webp', 'max:5120', new PassportPhotoFile(true)],
        ];
    }
}
