<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait DispatchesCollegeToasts
{
    protected function collegeToast(string $message, string $variant = 'success'): void
    {
        $this->dispatch('college-toast', message: $message, variant: $variant);
    }
}
