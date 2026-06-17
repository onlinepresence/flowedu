<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a single result score (used from Livewire grading UI).
 */
class UpdateResultScoreRequest extends FormRequest
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
        return [
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Rules for a draft field keyed by result id in Livewire state.
     *
     * @return array<string, mixed>
     */
    public static function draftScoreRules(int $resultId): array
    {
        return [
            'draftScores.'.$resultId => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
