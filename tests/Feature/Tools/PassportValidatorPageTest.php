<?php

declare(strict_types=1);

namespace Tests\Feature\Tools;

use App\Livewire\Tools\PassportValidatorPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class PassportValidatorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_public_passport_validator(): void
    {
        $this->get(route('passport.validator'))
            ->assertOk()
            ->assertSee(__('Test your passport photo'))
            ->assertSee(__('Current passport photo requirements'));
    }

    #[RequiresPhpExtension('gd')]
    public function test_guest_can_analyze_an_image_and_see_detailed_checks(): void
    {
        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.skip_ratio' => true,
            'image_validation.passport.min_width' => 300,
            'image_validation.passport.min_height' => 400,
            'image_validation.passport.bg_color' => ['r' => 255, 'g' => 0, 'b' => 0],
            'image_validation.passport.bg_tolerance' => 120,
            'image_validation.passport.edge_match_percent' => 60,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $image = imagecreatetruecolor(320, 420);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, 319, 419, $red);
        imagepng($image, $path);
        imagedestroy($image);

        try {
            Livewire::test(PassportValidatorPage::class)
                ->set('photo', UploadedFile::fake()->createWithContent('passport.png', (string) file_get_contents($path)))
                ->call('analyze')
                ->assertHasNoErrors()
                ->assertSet('result.accepted', true)
                ->assertSee(__('Photo accepted'))
                ->assertSee(__('Minimum resolution'))
                ->assertSee(__('Background colour'))
                ->assertSee('320 x 420 px');
        } finally {
            @unlink($path);
        }
    }

    #[RequiresPhpExtension('gd')]
    public function test_public_validator_explains_a_rejected_photo(): void
    {
        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.skip_ratio' => true,
            'image_validation.passport.min_width' => 300,
            'image_validation.passport.min_height' => 400,
            'image_validation.passport.bg_color' => ['r' => 255, 'g' => 0, 'b' => 0],
            'image_validation.passport.bg_tolerance' => 120,
            'image_validation.passport.edge_match_percent' => 60,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $image = imagecreatetruecolor(100, 120);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, 99, 119, $red);
        imagepng($image, $path);
        imagedestroy($image);

        try {
            Livewire::test(PassportValidatorPage::class)
                ->set('photo', UploadedFile::fake()->createWithContent('small.png', (string) file_get_contents($path)))
                ->call('analyze')
                ->assertSet('result.accepted', false)
                ->assertSee(__('Photo not accepted'))
                ->assertSee(__('Minimum resolution'))
                ->assertSee('100 x 120 px');
        } finally {
            @unlink($path);
        }
    }
}
