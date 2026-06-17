<?php

declare(strict_types=1);

namespace App\Services;

use GdImage;

/**
 * Ports legacy validate_passport_photo() from includes/image_validation.php (GD-based).
 */
final class PassportPhotoValidationService
{
    public function __construct(
        private readonly PassportValidationConfigResolver $passportConfigResolver,
    ) {}

    /**
     * @return array{status: bool, message: string}
     */
    public function validate(string $imagePath): array
    {
        $cfg = $this->passportConfigResolver->passportConfig();

        if (! (bool) ($cfg['enabled'] ?? true)) {
            return ['status' => true, 'message' => ''];
        }

        if (! is_readable($imagePath)) {
            return ['status' => false, 'message' => __('Image file not found.')];
        }
        $targetBg = [
            (int) ($cfg['bg_color']['r'] ?? 255),
            (int) ($cfg['bg_color']['g'] ?? 0),
            (int) ($cfg['bg_color']['b'] ?? 0),
        ];
        $bgTolerance = (int) ($cfg['bg_tolerance'] ?? 120);
        $minWidth = (int) ($cfg['min_width'] ?? 300);
        $minHeight = (int) ($cfg['min_height'] ?? 400);
        $skipRatioSize = (bool) ($cfg['skip_ratio'] ?? true);
        $aspectLabel = (string) ($cfg['aspect_ratio'] ?? '7:9');
        $aspectTolerance = (float) ($cfg['aspect_tolerance'] ?? 0.05);
        $matchPercent = (int) ($cfg['edge_match_percent'] ?? 60);
        $edgeDivisor = max(1, (int) ($cfg['edge_sample_divisor'] ?? 100));

        $sizeInfo = @getimagesize($imagePath);
        if ($sizeInfo === false) {
            return ['status' => false, 'message' => __('Could not read image dimensions.')];
        }

        [$width, $height] = $sizeInfo;

        if ($skipRatioSize && ! $this->isImageSizeAcceptable($width, $height, $minWidth, $minHeight)) {
            return [
                'status' => false,
                'message' => __('Image too small. Minimum required size is :w×:h pixels.', ['w' => $minWidth, 'h' => $minHeight]),
            ];
        }

        if (! $skipRatioSize && ! $this->isPassportAspectRatio($width, $height, $aspectTolerance, $aspectLabel)) {
            return [
                'status' => false,
                'message' => __('Invalid aspect ratio. Expected ratio close to :ratio.', ['ratio' => $aspectLabel]),
            ];
        }

        if (! $this->isBackgroundColorMatch($imagePath, $targetBg, $bgTolerance, $matchPercent, $edgeDivisor)) {
            return [
                'status' => false,
                'message' => __('Background color does not match expected color. Please use a solid background (e.g. red).'),
            ];
        }

        return ['status' => true, 'message' => __('Image is valid as a passport photo.')];
    }

    private function isImageSizeAcceptable(int $width, int $height, int $minWidth, int $minHeight): bool
    {
        return $width >= $minWidth && $height >= $minHeight;
    }

    private function parseAspectRatio(string $ratio): float
    {
        $parts = preg_split('/\s*:\s*/', trim($ratio), 2);
        if (count($parts) !== 2) {
            return 7 / 9;
        }
        $w = (float) $parts[0];
        $h = (float) $parts[1];
        if ($w <= 0 || $h <= 0) {
            return 7 / 9;
        }

        return $w / $h;
    }

    private function isPassportAspectRatio(int $width, int $height, float $tolerance, string $ratioString): bool
    {
        $actualRatio = $width / $height;
        $expectedRatio = $this->parseAspectRatio($ratioString);

        return abs($actualRatio - $expectedRatio) <= $tolerance;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $targetColor
     */
    private function isBackgroundColorMatch(
        string $imagePath,
        array $targetColor,
        int $tolerance,
        int $requiredMatchPercentage,
        int $edgeSampleDivisor
    ): bool {
        $requiredMatchPercentage = max(0, min(100, $requiredMatchPercentage));

        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        $image = $this->loadImage($imagePath, $type);
        if (! $image instanceof GdImage) {
            return false;
        }

        $samples = [];

        $cornerSize = min(20, (int) floor($width / 10), (int) floor($height / 10));
        $corners = [
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1],
        ];

        foreach ($corners as $corner) {
            $cx = $corner[0];
            $cy = $corner[1];
            for ($dx = 0; $dx < $cornerSize && $cx + $dx < $width; $dx++) {
                for ($dy = 0; $dy < $cornerSize && $cy + $dy < $height; $dy++) {
                    $samples[] = $this->getRgbAt($image, $cx + $dx, $cy + $dy);
                }
            }
        }

        $edgeStep = max(1, (int) floor(min($width, $height) / $edgeSampleDivisor));

        for ($x = 0; $x < $width; $x += $edgeStep) {
            $samples[] = $this->getRgbAt($image, $x, 0);
            $samples[] = $this->getRgbAt($image, $x, $height - 1);
        }

        for ($y = 0; $y < $height; $y += $edgeStep) {
            $samples[] = $this->getRgbAt($image, 0, $y);
            $samples[] = $this->getRgbAt($image, $width - 1, $y);
        }

        imagedestroy($image);

        if ($samples === []) {
            return false;
        }

        $matchingSamples = 0;
        $totalSamples = count($samples);
        $pixelTolerance = $tolerance * 1.2;

        foreach ($samples as $sample) {
            if ($this->isColorCloseEuclidean($sample, $targetColor, $pixelTolerance)) {
                $matchingSamples++;
            }
        }

        $matchPercentage = ($matchingSamples / $totalSamples) * 100;

        if ($matchPercentage >= $requiredMatchPercentage) {
            return true;
        }

        $avgColor = [
            (int) round(array_sum(array_column($samples, 0)) / $totalSamples),
            (int) round(array_sum(array_column($samples, 1)) / $totalSamples),
            (int) round(array_sum(array_column($samples, 2)) / $totalSamples),
        ];

        return $this->isColorCloseEuclidean($avgColor, $targetColor, $tolerance);
    }

    private function loadImage(string $imagePath, int $type): ?GdImage
    {
        if ($type === IMAGETYPE_JPEG) {
            $image = @imagecreatefromjpeg($imagePath);

            return $image instanceof GdImage ? $image : null;
        }

        if ($type === IMAGETYPE_PNG) {
            return $this->loadPng($imagePath);
        }

        if ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($imagePath);

            return $image instanceof GdImage ? $image : null;
        }

        if (defined('IMAGETYPE_AVIF') && $type === IMAGETYPE_AVIF && function_exists('imagecreatefromavif')) {
            $image = @imagecreatefromavif($imagePath);

            return $image instanceof GdImage ? $image : null;
        }

        $imageData = @file_get_contents($imagePath);
        if ($imageData === false) {
            return null;
        }

        $fromString = @imagecreatefromstring($imageData);

        return $fromString instanceof GdImage ? $fromString : null;
    }

    private function loadPng(string $path): ?GdImage
    {
        $image = @imagecreatefrompng($path);
        if ($image instanceof GdImage) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function getRgbAt(GdImage $image, int $x, int $y): array
    {
        $rgb = imagecolorat($image, $x, $y);
        if (imageistruecolor($image)) {
            return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
        }

        $colors = imagecolorsforindex($image, $rgb);

        return [(int) $colors['red'], (int) $colors['green'], (int) $colors['blue']];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color1
     * @param  array{0: int, 1: int, 2: int}  $color2
     */
    private function isColorCloseEuclidean(array $color1, array $color2, float $tolerance): bool
    {
        $distance = sqrt(
            ($color1[0] - $color2[0]) ** 2
            + ($color1[1] - $color2[1]) ** 2
            + ($color1[2] - $color2[2]) ** 2
        );

        return $distance <= $tolerance;
    }
}
