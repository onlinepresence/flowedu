<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tools;

use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class PassportValidatorPage extends Component
{
    public ?string $passportPond = null;

    /** @var array<int, string> */
    public array $messages = [];

    public function validatePassport(): void
    {
        $this->messages = [];
        $this->validate([
            'passportPond' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->passportPond, $userId)) {
            $this->messages[] = __('Could not read the uploaded file.');

            return;
        }

        $path = Storage::disk('local')->path($this->passportPond);
        if (! is_readable($path)) {
            $this->messages[] = __('Could not read the uploaded file.');

            return;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            $this->messages[] = __('Not a readable image.');

            return;
        }

        [$w, $h] = $info;
        $this->messages[] = __('Dimensions: :w × :h px', ['w' => $w, 'h' => $h]);

        if ($w < 200 || $h < 200) {
            $this->messages[] = __('Below 200×200 — likely too small for passport printing.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.tools.passport-validator-page')
            ->layout('components.layouts.admin', [
                'title' => __('Passport Validator'),
                'headerTitle' => __('Passport Image Validator Tool'),
                'headerDescription' => __('Dry-run test uploads to inspect dimension metadata constraints for printable student identification cards.'),
            ]);
    }
}
