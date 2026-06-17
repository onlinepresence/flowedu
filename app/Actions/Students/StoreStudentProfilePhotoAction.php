<?php

declare(strict_types=1);

namespace App\Actions\Students;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class StoreStudentProfilePhotoAction
{
    public function execute(TemporaryUploadedFile|UploadedFile $file, ?string $previousRelativePath): string
    {
        $disk = 'college_uploads';
        $dir = 'students/profiles';
        $ext = $file->guessExtension() ?: 'jpg';
        $name = Str::uuid()->toString().'.'.$ext;
        $stored = $file->storeAs($dir, $name, $disk);

        if ($previousRelativePath !== null
            && $previousRelativePath !== ''
            && ! Str::contains($previousRelativePath, '..')
            && $previousRelativePath !== 'placeholder.png') {
            Storage::disk($disk)->delete($previousRelativePath);
        }

        return $stored;
    }
}
