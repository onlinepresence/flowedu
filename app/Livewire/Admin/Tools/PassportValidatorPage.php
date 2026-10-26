<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tools;

use App\Livewire\Tools\PassportValidatorPage as BasePassportValidatorPage;
use App\Services\PassportPhotoValidationService;
use Illuminate\Contracts\View\View;

class PassportValidatorPage extends BasePassportValidatorPage
{
    public function render(): View
    {
        return view('livewire.tools.passport-validator-page', [
            'requirements' => app(PassportPhotoValidationService::class)->requirements(),
        ])->layout('components.layouts.admin', [
            'title' => __('Passport Validator'),
            'headerTitle' => __('Passport Image Validator Tool'),
            'headerDescription' => __('Test a passport photo against the current school image validation settings before a student submits it.'),
        ]);
    }
}
