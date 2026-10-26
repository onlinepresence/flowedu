<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\ImageValidationPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PassportPhotoValidationService;
use App\Services\PassportValidationConfigResolver;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class ImageValidationPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingSystemAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'system_admin')->value('id');
        $this->assertNotNull($roleId);

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'sysimgval',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_save_persists_settings_rows(): void
    {
        $user = $this->actingSystemAdmin();

        Livewire::actingAs($user)
            ->test(ImageValidationPage::class)
            ->set('passport_bg_color_r', 200)
            ->set('passport_bg_color_g', 10)
            ->set('passport_bg_color_b', 20)
            ->set('passport_tolerance', 100)
            ->set('passport_min_width', 280)
            ->set('passport_min_height', 360)
            ->set('passport_match_percentage', 55)
            ->set('passport_aspect_ratio', '3:4')
            ->set('passport_skip_ratio', false)
            ->set('passport_edge_sample_divisor', 120)
            ->call('saveSettings')
            ->assertRedirect(route('admin.settings.image-validation'));

        $this->assertSame('200', Setting::query()->where('setting_key', 'image_validation.passport_bg_color_r')->value('setting_value'));
        $this->assertSame('3:4', Setting::query()->where('setting_key', 'image_validation.passport_aspect_ratio')->value('setting_value'));
        $this->assertSame('0', Setting::query()->where('setting_key', 'image_validation.passport_skip_ratio')->value('setting_value'));
    }

    public function test_admin_passport_validator_uses_the_shared_validator_page(): void
    {
        $user = $this->actingSystemAdmin();

        $this->actingAs($user)
            ->get(route('tools.passport-validator'))
            ->assertOk()
            ->assertSee(__('Test your passport photo'))
            ->assertSee(__('Current passport photo requirements'));
    }

    #[RequiresPhpExtension('gd')]
    public function test_sandbox_uses_the_colour_selected_in_the_realtime_preview(): void
    {
        $user = $this->actingSystemAdmin();

        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.bg_color' => ['r' => 255, 'g' => 0, 'b' => 0],
        ]);

        $pending = 'filepond-tmp/'.$user->id.'/blue-preview.png';
        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $image = imagecreatetruecolor(320, 420);
        $blue = imagecolorallocate($image, 0, 0, 255);
        imagefilledrectangle($image, 0, 0, 319, 419, $blue);
        imagepng($image, $path);
        imagedestroy($image);
        Storage::disk('local')->put($pending, (string) file_get_contents($path));

        try {
            Livewire::actingAs($user)
                ->test(ImageValidationPage::class)
                ->set('passport_bg_color_r', 0)
                ->set('passport_bg_color_g', 0)
                ->set('passport_bg_color_b', 255)
                ->set('passport_tolerance', 120)
                ->set('passport_min_width', 300)
                ->set('passport_min_height', 400)
                ->set('passport_match_percentage', 60)
                ->set('passport_skip_ratio', true)
                ->set('passport_edge_sample_divisor', 100)
                ->set('testPhotoPond', $pending)
                ->call('runTestUpload')
                ->assertSet('testMessages.0', __('Validation passed.'));
        } finally {
            Storage::disk('local')->delete($pending);
            @unlink($path);
        }
    }

    #[RequiresPhpExtension('gd')]
    public function test_resolver_overrides_config_for_validation(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        Setting::query()->create([
            'category' => 'image_validation',
            'setting_key' => 'image_validation.passport_min_width',
            'setting_value' => '900',
            'data_type' => 'integer',
            'description' => 'test',
            'updated_by' => null,
        ]);
        Setting::query()->create([
            'category' => 'image_validation',
            'setting_key' => 'image_validation.passport_min_height',
            'setting_value' => '900',
            'data_type' => 'integer',
            'description' => 'test',
            'updated_by' => null,
        ]);

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
        $im = imagecreatetruecolor(400, 400);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefilledrectangle($im, 0, 0, 399, 399, $red);
        imagepng($im, $path);
        imagedestroy($im);

        try {
            $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
            $r = $svc->validate($path);
            $this->assertFalse($r['status'], '900×900 min should reject 400×400 image');
        } finally {
            @unlink($path);
        }
    }
}
