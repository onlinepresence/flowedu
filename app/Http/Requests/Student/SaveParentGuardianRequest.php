<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Rules\GhanaMobilePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Legacy submit save_guardian (student/submit.php).
 */
class SaveParentGuardianRequest extends FormRequest
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
        $student = Student::query()->where('user_id', auth()->id())->firstOrFail();

        return self::rulesForStudent($student->id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesForStudent(int $studentId): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::in([$studentId])],
            'guardian_id' => ['nullable', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', new GhanaMobilePhone],
            'address' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function messages(): array
    {
        return [
            'student_id.required' => __('Student data could not be verified.'),
            'student_id.in' => __('Student specified was not found.'),
        ];
    }
}
