<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Self-service cancel registration (legacy delete-account) + current password.
 */
class DeleteStudentAccountRequest extends FormRequest
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
        return self::rulesFor((int) auth()->id());
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesFor(int $userId): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::in([$userId])],
            'password' => ['required', 'current_password'],
        ];
    }
}
