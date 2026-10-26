<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Services\PassportPhotoValidationService;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class PassportValidatorPage extends Component
{
    public ?string $photo = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $result = null;

    public function updatedPhoto(): void
    {
        $this->result = null;
        $this->resetValidation('photo');
    }

    public function analyze(PassportPhotoValidationService $service): void
    {
        $this->validate([
            'photo' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->photo, $userId)) {
            $this->addError('photo', __('The uploaded photo is invalid.'));

            return;
        }

        $path = Storage::disk('local')->path($this->photo);

        if (! is_readable($path)) {
            $this->addError('photo', __('The uploaded photo could not be read.'));

            return;
        }

        $result = $service->inspect($path);
        $result['metadata']['filename'] = basename($this->photo);
        $this->result = $result;
    }

    public function render(): View
    {
        return view('livewire.tools.passport-validator-page', [
            'requirements' => app(PassportPhotoValidationService::class)->requirements(),
        ])->layout('components.layouts.public', [
            'title' => __('Passport photo validator'),
            'headerTitle' => __('Passport photo validator'),
            'headerDescription' => __('Test your passport photo before uploading it. The same checks are applied when your registration is submitted.'),
        ]);
    }
}
