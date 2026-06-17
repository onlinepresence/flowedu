<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TeacherResultsUploadPage extends Component
{
    use DispatchesCollegeToasts;

    public ?string $spreadsheetPond = null;

    public ?int $detectedRows = null;

    public function analyze(): void
    {
        $this->detectedRows = null;
        $this->validate([
            'spreadsheetPond' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->spreadsheetPond, $userId)) {
            $this->addError('spreadsheetPond', __('Could not read file.'));

            return;
        }
        $path = Storage::disk('local')->path($this->spreadsheetPond);

        try {
            $spreadsheet = IOFactory::load($path);
            $this->detectedRows = max(0, $spreadsheet->getActiveSheet()->getHighestDataRow());
        } catch (\Throwable) {
            $this->addError('spreadsheetPond', __('Could not parse spreadsheet.'));

            return;
        }

        $this->collegeToast(__('Rows detected: :n (import not run).', ['n' => $this->detectedRows]));
    }

    public function render(): View
    {
        return view('livewire.teacher.teacher-results-upload-page')
            ->layout('components.layouts.teacher', [
                'title' => __('Upload results'),
                'headerDescription' => __('Spreadsheet preview for results upload (mapping to follow).'),
            ]);
    }
}
