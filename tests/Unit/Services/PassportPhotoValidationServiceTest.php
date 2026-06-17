<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PassportPhotoValidationService;
use App\Services\PassportValidationConfigResolver;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('gd')]
class PassportPhotoValidationServiceTest extends TestCase
{
    private function makeRedPassportPng(string $path, int $w, int $h): void
    {
        $im = imagecreatetruecolor($w, $h);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $red);
        imagepng($im, $path);
        imagedestroy($im);
    }

    public function test_accepts_large_red_background_when_ratio_check_skipped(): void
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
        $this->makeRedPassportPng($path, 320, 420);

        try {
            $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
            $r = $svc->validate($path);
            $this->assertTrue($r['status'], $r['message'] ?? '');
        } finally {
            @unlink($path);
        }
    }

    public function test_rejects_too_small_image(): void
    {
        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.skip_ratio' => true,
            'image_validation.passport.min_width' => 300,
            'image_validation.passport.min_height' => 400,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $this->makeRedPassportPng($path, 50, 50);

        try {
            $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
            $r = $svc->validate($path);
            $this->assertFalse($r['status']);
        } finally {
            @unlink($path);
        }
    }

    public function test_disabled_short_circuits_to_success(): void
    {
        config(['image_validation.passport.enabled' => false]);

        $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
        $r = $svc->validate('/nonexistent/path.png');
        $this->assertTrue($r['status']);
    }
}
