<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Services\PassportPhotoValidationService;
use App\Services\PassportValidationConfigResolver;
use App\Support\CollegeFlash;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ImageValidationPage extends Component
{
    public int $passport_bg_color_r = 255;

    public int $passport_bg_color_g = 0;

    public int $passport_bg_color_b = 0;

    public int $passport_tolerance = 120;

    public int $passport_min_width = 300;

    public int $passport_min_height = 400;

    public int $passport_match_percentage = 60;

    public string $passport_aspect_ratio = '7:9';

    public bool $passport_skip_ratio = true;

    public int $passport_edge_sample_divisor = 100;

    public ?string $testPhotoPond = null;

    /** @var array<int, string> */
    public array $testMessages = [];

    public function mount(PassportValidationConfigResolver $resolver): void
    {
        $this->hydrateFromConfig($resolver->passportConfig());
    }

    public function saveSettings(): void
    {
        $this->validate([
            'passport_bg_color_r' => ['required', 'integer', 'min:0', 'max:255'],
            'passport_bg_color_g' => ['required', 'integer', 'min:0', 'max:255'],
            'passport_bg_color_b' => ['required', 'integer', 'min:0', 'max:255'],
            'passport_tolerance' => ['required', 'integer', 'min:0', 'max:441'],
            'passport_min_width' => ['required', 'integer', 'min:1'],
            'passport_min_height' => ['required', 'integer', 'min:1'],
            'passport_match_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'passport_aspect_ratio' => ['required', 'string', 'in:7:9,3:4,1:1'],
            'passport_edge_sample_divisor' => ['required', 'integer', 'min:10', 'max:500'],
            'passport_skip_ratio' => ['boolean'],
        ]);

        $userId = auth()->id();
        $defs = [
            'image_validation.passport_bg_color_r' => ['val' => (string) $this->passport_bg_color_r, 'type' => 'integer', 'desc' => 'Passport background R'],
            'image_validation.passport_bg_color_g' => ['val' => (string) $this->passport_bg_color_g, 'type' => 'integer', 'desc' => 'Passport background G'],
            'image_validation.passport_bg_color_b' => ['val' => (string) $this->passport_bg_color_b, 'type' => 'integer', 'desc' => 'Passport background B'],
            'image_validation.passport_tolerance' => ['val' => (string) $this->passport_tolerance, 'type' => 'integer', 'desc' => 'Passport background color tolerance'],
            'image_validation.passport_min_width' => ['val' => (string) $this->passport_min_width, 'type' => 'integer', 'desc' => 'Passport min width px'],
            'image_validation.passport_min_height' => ['val' => (string) $this->passport_min_height, 'type' => 'integer', 'desc' => 'Passport min height px'],
            'image_validation.passport_match_percentage' => ['val' => (string) $this->passport_match_percentage, 'type' => 'integer', 'desc' => 'Passport edge match percent'],
            'image_validation.passport_aspect_ratio' => ['val' => $this->passport_aspect_ratio, 'type' => 'string', 'desc' => 'Passport aspect ratio label'],
            'image_validation.passport_skip_ratio' => ['val' => $this->passport_skip_ratio ? '1' : '0', 'type' => 'boolean', 'desc' => 'Skip aspect ratio check'],
            'image_validation.passport_edge_sample_divisor' => ['val' => (string) $this->passport_edge_sample_divisor, 'type' => 'integer', 'desc' => 'Passport edge sample divisor'],
        ];

        foreach ($defs as $key => $meta) {
            $row = Setting::query()->firstOrNew(['setting_key' => $key]);
            $row->forceFill([
                'setting_value' => $meta['val'],
                'category' => 'image_validation',
                'data_type' => $meta['type'],
                'description' => $meta['desc'],
                'updated_by' => $userId,
            ])->save();
        }

        CollegeFlash::forNextRequestToo('status', __('Image validation settings saved.'));
        $this->redirect(route('admin.settings.image-validation'), navigate: true);
    }

    public function runTestUpload(PassportPhotoValidationService $service): void
    {
        $this->testMessages = [];
        $this->validate([
            'testPhotoPond' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->testPhotoPond, $userId)) {
            $this->testMessages[] = __('Could not read the uploaded file.');

            return;
        }

        $path = Storage::disk('local')->path($this->testPhotoPond);
        if (! is_readable($path)) {
            $this->testMessages[] = __('Could not read the uploaded file.');

            return;
        }

        $result = $service->validate($path);
        $this->testMessages[] = $result['message'] !== ''
            ? $result['message']
            : ($result['status'] ? __('Validation passed.') : __('Validation failed.'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.image-validation-page')
            ->layout('components.layouts.admin', [
                'title' => __('Image validation'),
                'headerTitle' => __('Passport Photo Validation Settings'),
                'headerDescription' => __('Configure background color matching, resolution, aspect ratio boundaries, and threshold parameters for student passport photo submissions.'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function hydrateFromConfig(array $cfg): void
    {
        $bg = is_array($cfg['bg_color'] ?? null) ? $cfg['bg_color'] : [];
        $this->passport_bg_color_r = (int) ($bg['r'] ?? 255);
        $this->passport_bg_color_g = (int) ($bg['g'] ?? 0);
        $this->passport_bg_color_b = (int) ($bg['b'] ?? 0);
        $this->passport_tolerance = (int) ($cfg['bg_tolerance'] ?? 120);
        $this->passport_min_width = (int) ($cfg['min_width'] ?? 300);
        $this->passport_min_height = (int) ($cfg['min_height'] ?? 400);
        $this->passport_match_percentage = (int) ($cfg['edge_match_percent'] ?? 60);
        $this->passport_aspect_ratio = (string) ($cfg['aspect_ratio'] ?? '7:9');
        if (! in_array($this->passport_aspect_ratio, ['7:9', '3:4', '1:1'], true)) {
            $this->passport_aspect_ratio = '7:9';
        }
        $this->passport_skip_ratio = (bool) ($cfg['skip_ratio'] ?? true);
        $this->passport_edge_sample_divisor = (int) ($cfg['edge_sample_divisor'] ?? 100);
    }
}
