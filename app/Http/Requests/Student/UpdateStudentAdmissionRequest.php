<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Rules\GhanaCardNumber;
use App\Rules\GhanaMobilePhone;
use App\Rules\PassportPhotoFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Legacy submit update_student validation (student/submit.php).
 */
class UpdateStudentAdmissionRequest extends FormRequest
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
        $student = Student::query()->where('user_id', $userId)->firstOrFail();

        return self::rulesForStudent($student);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesForStudent(Student $student): array
    {
        $userId = (int) $student->user_id;

        return [
            'user_id' => ['required', 'integer', Rule::in([$userId])],
            'index_number' => ['required', 'string', 'max:64'],
            'lastname' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'othernames' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:100'],
            'insurance_number' => ['nullable', 'numeric'],
            'ghana_card' => ['required', 'string', new GhanaCardNumber, Rule::unique('students', 'ghana_card')->ignore($student->id)],
            'contact_address' => ['required', 'string', 'max:2000'],
            'phone_number' => ['required', 'string', new GhanaMobilePhone, Rule::unique('students', 'phone_number')->ignore($student->id)],
            'religion' => ['nullable', 'string', 'max:255'],
            'denomination' => ['nullable', 'string', 'max:100'],
            'disability_status' => ['nullable', 'string', Rule::in(['no', 'yes'])],
            'disability_type' => ['nullable', 'required_if:disability_status,yes', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'account_bank' => ['nullable', 'required_with:account_number', 'string', 'max:255'],
            'account_number' => ['nullable', 'required_with:account_bank', 'string', 'max:64', Rule::unique('students', 'account_number')->ignore($student->id)],
            'ssnit_number' => ['nullable', 'numeric'],
            'profile_pic' => ['nullable', 'file', 'mimes:jpg,jpeg,png,avif,webp', 'max:5120', new PassportPhotoFile(false)],
        ];
    }
}
