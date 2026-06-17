<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\PassportPhotoValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Legacy create_student / change_picture passport checks (after MIME/size rules).
 */
final class PassportPhotoFile implements ValidationRule
{
    public function __construct(
        private readonly bool $required = false
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            if ($this->required) {
                $fail(__('A passport-style profile photo is required.'));
            }

            return;
        }

        if (! $value instanceof UploadedFile && ! $value instanceof TemporaryUploadedFile) {
            $fail(__('The :attribute must be a file.', ['attribute' => $attribute]));

            return;
        }

        if (! $value->isValid()) {
            $fail(__('The :attribute upload failed.', ['attribute' => $attribute]));

            return;
        }

        $path = $value->getRealPath();
        if ($path === false || ! is_readable($path)) {
            $fail(__('The :attribute could not be read.', ['attribute' => $attribute]));

            return;
        }

        $result = app(PassportPhotoValidationService::class)->validate($path);
        if (! $result['status']) {
            $fail($result['message']);
        }
    }
}
