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
        $this->makeSolidPng($path, $w, $h, 255, 0, 0);
    }

    private function makeSolidPng(string $path, int $w, int $h, int $r, int $g, int $b): void
    {
        $im = imagecreatetruecolor($w, $h);
        $colour = imagecolorallocate($im, $r, $g, $b);
        imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $colour);
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

    public function test_inspect_reports_every_check_and_size_failure(): void
    {
        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.skip_ratio' => true,
            'image_validation.passport.min_width' => 300,
            'image_validation.passport.min_height' => 400,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $this->makeRedPassportPng($path, 100, 120);

        try {
            $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
            $result = $svc->inspect($path);
            $checks = collect($result['checks'])->keyBy('key');

            $this->assertFalse($result['accepted']);
            $this->assertSame(100, $result['metadata']['width']);
            $this->assertSame(120, $result['metadata']['height']);
            $this->assertSame('pass', $checks['file']['state']);
            $this->assertSame('fail', $checks['size']['state']);
            $this->assertSame('skip', $checks['aspect_ratio']['state']);
            $this->assertSame('pass', $checks['background']['state']);
            $this->assertStringContainsString('too small', $result['summary']);
        } finally {
            @unlink($path);
        }
    }

    public function test_inspect_reports_ratio_failure_when_ratio_check_enabled(): void
    {
        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.skip_ratio' => false,
            'image_validation.passport.min_width' => 300,
            'image_validation.passport.min_height' => 300,
            'image_validation.passport.aspect_ratio' => '7:9',
            'image_validation.passport.aspect_tolerance' => 0.05,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $this->makeRedPassportPng($path, 400, 400);

        try {
            $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
            $result = $svc->inspect($path);
            $checks = collect($result['checks'])->keyBy('key');

            $this->assertFalse($result['accepted']);
            $this->assertSame('pass', $checks['size']['state']);
            $this->assertSame('fail', $checks['aspect_ratio']['state']);
            $this->assertStringContainsString('Expected 7:9', $checks['aspect_ratio']['detail']);
        } finally {
            @unlink($path);
        }
    }

    public function test_inspect_reports_background_match_details(): void
    {
        config([
            'image_validation.passport.enabled' => true,
            'image_validation.passport.skip_ratio' => true,
            'image_validation.passport.min_width' => 300,
            'image_validation.passport.min_height' => 300,
            'image_validation.passport.bg_color' => ['r' => 255, 'g' => 0, 'b' => 0],
            'image_validation.passport.bg_tolerance' => 20,
            'image_validation.passport.edge_match_percent' => 60,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pp').'.png';
        $this->makeSolidPng($path, 300, 400, 0, 255, 0);

        try {
            $svc = new PassportPhotoValidationService(new PassportValidationConfigResolver);
            $result = $svc->inspect($path);
            $checks = collect($result['checks'])->keyBy('key');

            $this->assertFalse($result['accepted']);
            $this->assertSame('fail', $checks['background']['state']);
            $this->assertStringContainsString('Background must be red', $checks['background']['detail']);
            $this->assertStringContainsString('Your photo has a green background', $checks['background']['detail']);
        } finally {
            @unlink($path);
        }
    }
}
