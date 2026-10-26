<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Services\PassportPhotoValidationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class PassportValidatorPage extends Component
{
    use WithFileUploads;

    public $photo = null;

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
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
        ]);

        $result = $service->inspect($this->photo->getRealPath());
        $result['metadata']['filename'] = $this->photo->getClientOriginalName();
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
