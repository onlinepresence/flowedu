<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\PassportPhotoValidationService;
use App\Support\FilepondPendingFile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Runs the passport-photo checks against a FilePond pending-upload path
 * (students upload the photo asynchronously, so the Livewire form only ever
 * receives the relative temp path, never an UploadedFile).
 */
final class PassportPhotoPendingFile implements ValidationRule
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

        $userId = Auth::id();

        if (! is_string($value) || $userId === null || ! FilepondPendingFile::assertOwnedPendingPath($value, $userId)) {
            $fail(__('The :attribute upload is invalid.', ['attribute' => $attribute]));

            return;
        }

        $path = Storage::disk('local')->path($value);
        if (! is_readable($path)) {
            $fail(__('The :attribute could not be read.', ['attribute' => $attribute]));

            return;
        }

        $result = app(PassportPhotoValidationService::class)->validate($path);
        if (! $result['status']) {
            $fail($result['message']);
        }
    }
}
