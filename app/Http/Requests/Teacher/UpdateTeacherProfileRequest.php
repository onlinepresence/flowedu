<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use App\Models\Teacher;
use App\Rules\GhanaCardNumber;
use App\Rules\GhanaMobilePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Legacy teacher/submit.php update_teacher validation.
 */
final class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()?->type === 'teacher';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacher = auth()->user()?->teacher;
        abort_if($teacher === null, 404);

        return self::rulesForTeacher($teacher);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesForTeacher(Teacher $teacher): array
    {
        $userId = (int) $teacher->user_id;

        return [
            'user_id' => ['required', 'integer', Rule::in([$userId])],
            'lastname' => ['required', 'string', 'max:255'],
            'othernames' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:30'],
            'ghana_card' => ['required', 'string', new GhanaCardNumber],
            'contact_address' => ['required', 'string', 'max:500'],
            'phone_number' => ['required', 'string', new GhanaMobilePhone],
            'staff_id' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'office_location' => ['nullable', 'string', 'max:150'],
            'office_hours' => ['nullable', 'string', 'max:200'],
            'rank' => ['nullable', 'string', 'max:100', Rule::in(array_keys(self::rankOptions()))],
            'qualification' => ['nullable', 'string', 'max:100', Rule::in(array_keys(self::qualificationOptions()))],
            'specialization' => ['required', 'string', 'max:255'],
            'orcid_id' => ['nullable', 'string', 'max:50'],
            'google_scholar_url' => ['nullable', 'url', 'max:255'],
            'employment_type' => ['nullable', 'string', Rule::in(['Full-time', 'Part-time', 'Visiting'])],
            'years_experience' => ['required', 'integer', 'min:0', 'max:50'],
            'emergency_name' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'research_interests' => ['nullable', 'string', 'max:2000'],
            'date_of_appointment' => ['required', 'date', 'before:tomorrow'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function rankOptions(): array
    {
        $ranks = [
            'Principal Chief Instructor',
            'Chief Instructor',
            'Assistant Lecturer',
            'Lecturer',
            'Senior Lecturer',
            'Associate Professor',
            'Professor',
        ];

        return array_combine($ranks, $ranks) ?: [];
    }

    /**
     * @return array<string, string>
     */
    public static function qualificationOptions(): array
    {
        $q = ['PhD', 'MPhil', 'MSc', 'B.Ed', 'BSc', 'Other'];

        return array_combine($q, $q) ?: [];
    }
}
