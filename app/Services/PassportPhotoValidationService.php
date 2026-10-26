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
     * @return array{
     *     enabled: bool,
     *     min_width: int,
     *     min_height: int,
     *     skip_ratio: bool,
     *     aspect_ratio: string,
     *     aspect_tolerance: float,
     *     bg_color: array{r: int, g: int, b: int},
     *     bg_tolerance: int,
     *     edge_match_percent: int,
     *     edge_sample_divisor: int
     * }
     */
    public function requirements(): array
    {
        $cfg = $this->passportConfigResolver->passportConfig();
        $bg = is_array($cfg['bg_color'] ?? null) ? $cfg['bg_color'] : [];

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? true),
            'min_width' => (int) ($cfg['min_width'] ?? 300),
            'min_height' => (int) ($cfg['min_height'] ?? 400),
            'skip_ratio' => (bool) ($cfg['skip_ratio'] ?? true),
            'aspect_ratio' => (string) ($cfg['aspect_ratio'] ?? '7:9'),
            'aspect_tolerance' => (float) ($cfg['aspect_tolerance'] ?? 0.05),
            'bg_color' => [
                'r' => (int) ($bg['r'] ?? 255),
                'g' => (int) ($bg['g'] ?? 0),
                'b' => (int) ($bg['b'] ?? 0),
            ],
            'bg_tolerance' => (int) ($cfg['bg_tolerance'] ?? 120),
            'edge_match_percent' => (int) ($cfg['edge_match_percent'] ?? 60),
            'edge_sample_divisor' => max(1, (int) ($cfg['edge_sample_divisor'] ?? 100)),
        ];
    }

    /**
     * @return array{status: bool, message: string}
     */
    public function validate(string $imagePath): array
    {
        $requirements = $this->requirements();

        if (! $requirements['enabled']) {
            return ['status' => true, 'message' => ''];
        }

        $result = $this->inspect($imagePath, $requirements);

        return [
            'status' => $result['accepted'],
            'message' => $result['accepted']
                ? __('Image is valid as a passport photo.')
                : $result['summary'],
        ];
    }

    /**
     * Apply every configured check and report each one individually.
     *
     * @param  array<string, mixed>|null  $requirements
     * @return array{
     *     enabled: bool,
     *     accepted: bool,
     *     summary: string,
     *     metadata: array{width: int|null, height: int|null, mime: string|null, size_bytes: int|null},
     *     checks: list<array{key: string, label: string, state: string, detail: string}>
     * }
     */
    public function inspect(string $imagePath, ?array $requirements = null): array
    {
        $req = $requirements ?? $this->requirements();
        $sizeBytes = @filesize($imagePath);
        $metadata = [
            'width' => null,
            'height' => null,
            'mime' => null,
            'size_bytes' => is_int($sizeBytes) ? $sizeBytes : null,
        ];

        if (! $req['enabled']) {
            return [
                'enabled' => false,
                'accepted' => true,
                'summary' => __('Passport photo validation is currently disabled. Any readable image will be accepted.'),
                'metadata' => $metadata,
                'checks' => [[
                    'key' => 'enabled',
                    'label' => __('Validation setting'),
                    'state' => 'skip',
                    'detail' => __('Validation is turned off in the school settings, so no passport checks are enforced.'),
                ]],
            ];
        }

        if (! is_readable($imagePath)) {
            return [
                'enabled' => true,
                'accepted' => false,
                'summary' => __('Image file not found or could not be read.'),
                'metadata' => $metadata,
                'checks' => [[
                    'key' => 'file',
                    'label' => __('Image file'),
                    'state' => 'fail',
                    'detail' => __('The uploaded file could not be read. Please upload the image again.'),
                ]],
            ];
        }

        $sizeInfo = @getimagesize($imagePath);
        if ($sizeInfo === false) {
            return [
                'enabled' => true,
                'accepted' => false,
                'summary' => __('Could not read image dimensions.'),
                'metadata' => $metadata,
                'checks' => [[
                    'key' => 'file',
                    'label' => __('Image file'),
                    'state' => 'fail',
                    'detail' => __('This file is not a readable image. Use a JPG, PNG, WebP, or AVIF image.'),
                ]],
            ];
        }

        [$width, $height] = $sizeInfo;
        $metadata['width'] = $width;
        $metadata['height'] = $height;
        $metadata['mime'] = isset($sizeInfo['mime']) ? (string) $sizeInfo['mime'] : null;

        $checks = [];
        $checks[] = [
            'key' => 'file',
            'label' => __('Image file'),
            'state' => 'pass',
            'detail' => __('Readable :mime image, :size.', [
                'mime' => $metadata['mime'] ?: __('unknown format'),
                'size' => $this->formatBytes($metadata['size_bytes']),
            ]),
        ];

        $sizeAccepted = $this->isImageSizeAcceptable($width, $height, $req['min_width'], $req['min_height']);
        $checks[] = [
            'key' => 'size',
            'label' => __('Minimum resolution'),
            'state' => $sizeAccepted ? 'pass' : 'fail',
            'detail' => $sizeAccepted
                ? __('Image is :w x :h px. Minimum required is :minw x :minh px.', [
                    'w' => $width,
                    'h' => $height,
                    'minw' => $req['min_width'],
                    'minh' => $req['min_height'],
                ])
                : __('Image is too small at :w x :h px. Minimum required is :minw x :minh px.', [
                    'w' => $width,
                    'h' => $height,
                    'minw' => $req['min_width'],
                    'minh' => $req['min_height'],
                ]),
        ];

        if ($req['skip_ratio']) {
            $checks[] = [
                'key' => 'aspect_ratio',
                'label' => __('Aspect ratio'),
                'state' => 'skip',
                'detail' => __('Aspect ratio checking is disabled in the school settings.'),
            ];
        } else {
            $actualRatio = $width / $height;
            $expectedRatio = $this->parseAspectRatio($req['aspect_ratio']);
            $ratioAccepted = $this->isPassportAspectRatio($width, $height, $req['aspect_tolerance'], $req['aspect_ratio']);
            $checks[] = [
                'key' => 'aspect_ratio',
                'label' => __('Aspect ratio'),
                'state' => $ratioAccepted ? 'pass' : 'fail',
                'detail' => __('Expected :expected (:expected_value, tolerance +/-:tolerance). Your image is :w x :h px (:actual_value).', [
                    'expected' => $req['aspect_ratio'],
                    'expected_value' => number_format($expectedRatio, 3),
                    'tolerance' => number_format($req['aspect_tolerance'], 3),
                    'w' => $width,
                    'h' => $height,
                    'actual_value' => number_format($actualRatio, 3),
                ]),
            ];
        }

        $background = $this->analyzeBackground(
            $imagePath,
            [$req['bg_color']['r'], $req['bg_color']['g'], $req['bg_color']['b']],
            $req['bg_tolerance'],
            $req['edge_match_percent'],
            $req['edge_sample_divisor'],
        );
        $checks[] = [
            'key' => 'background',
            'label' => __('Background colour'),
            'state' => $background['matched'] ? 'pass' : 'fail',
            'detail' => $this->describeBackgroundResult($background, $req),
        ];

        $failed = array_values(array_filter($checks, fn (array $check): bool => $check['state'] === 'fail'));
        $accepted = $failed === [];

        return [
            'enabled' => true,
            'accepted' => $accepted,
            'summary' => $accepted
                ? __('Image is valid as a passport photo.')
                : $failed[0]['detail'],
            'metadata' => $metadata,
            'checks' => $checks,
        ];
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
     * @return array{
     *     matched: bool,
     *     matched_by_edges: bool,
     *     matched_by_average: bool,
     *     match_percentage: float,
     *     required_percentage: int,
     *     average_color: array{0: int, 1: int, 2: int}|null,
     *     tolerance: int
     * }
     */
    private function analyzeBackground(
        string $imagePath,
        array $targetColor,
        int $tolerance,
        int $requiredMatchPercentage,
        int $edgeSampleDivisor
    ): array {
        $requiredMatchPercentage = max(0, min(100, $requiredMatchPercentage));
        $default = [
            'matched' => false,
            'matched_by_edges' => false,
            'matched_by_average' => false,
            'match_percentage' => 0.0,
            'required_percentage' => $requiredMatchPercentage,
            'average_color' => null,
            'tolerance' => $tolerance,
        ];

        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            return $default;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        $image = $this->loadImage($imagePath, $type);
        if (! $image instanceof GdImage) {
            return $default;
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
            return $default;
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
        $matchedByEdges = $matchPercentage >= $requiredMatchPercentage;

        $averageColor = [
            (int) round(array_sum(array_column($samples, 0)) / $totalSamples),
            (int) round(array_sum(array_column($samples, 1)) / $totalSamples),
            (int) round(array_sum(array_column($samples, 2)) / $totalSamples),
        ];
        $matchedByAverage = $this->isColorCloseEuclidean($averageColor, $targetColor, $tolerance);

        return [
            'matched' => $matchedByEdges || $matchedByAverage,
            'matched_by_edges' => $matchedByEdges,
            'matched_by_average' => $matchedByAverage,
            'match_percentage' => $matchPercentage,
            'required_percentage' => $requiredMatchPercentage,
            'average_color' => $averageColor,
            'tolerance' => $tolerance,
        ];
    }

    /**
     * @param  array{
     *     matched: bool,
     *     matched_by_edges: bool,
     *     matched_by_average: bool,
     *     match_percentage: float,
     *     required_percentage: int,
     *     average_color: array{0: int, 1: int, 2: int}|null,
     *     tolerance: int
     * }  $background
     * @param  array<string, mixed>  $requirements
     */
    private function describeBackgroundResult(array $background, array $requirements): string
    {
        $expectedRgb = [
            $requirements['bg_color']['r'],
            $requirements['bg_color']['g'],
            $requirements['bg_color']['b'],
        ];
        $expected = $this->getColorName($expectedRgb);

        $actual = $background['average_color'] === null
            ? __('unknown')
            : $this->getColorName($background['average_color']);
        $percentage = number_format($background['match_percentage'], 1);

        if ($background['matched_by_edges']) {
            return __('Background matches :expected. :actual% of edge samples are within tolerance (minimum :required%).', [
                'expected' => $expected,
                'actual' => $percentage,
                'required' => $background['required_percentage'],
            ]);
        }

        if ($background['matched_by_average']) {
            return __('Edge match was only :actual% (minimum :required%), but the average edge colour :average is within tolerance of :expected.', [
                'actual' => $percentage,
                'required' => $background['required_percentage'],
                'average' => $actual,
                'expected' => $expected,
            ]);
        }

        return __('Background does not match :expected. :actual% of edge samples pass (minimum :required%), and the average edge colour is :average. Use a plain, solid :expected background.', [
            'expected' => $expected,
            'actual' => $percentage,
            'required' => $background['required_percentage'],
            'average' => $actual,
        ]);
    }

    private function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return __('unknown size');
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
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

    /**
     * Convert RGB to human-readable color name.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function getColorName(array $rgb): string
    {
        [$r, $g, $b] = $rgb;

        // White and near-white
        if ($r > 200 && $g > 200 && $b > 200) {
            return 'white';
        }

        // Black and near-black
        if ($r < 50 && $g < 50 && $b < 50) {
            return 'black';
        }

        // Red and variations
        if ($r > 180 && $g < 100 && $b < 100) {
            return 'red';
        }

        // Blue and variations
        if ($b > 180 && $r < 100 && $g < 100) {
            return 'blue';
        }

        // Light blue (common passport background)
        if ($b > 150 && $g > 150 && $r < 150 && $b > $r && $b > $g) {
            return 'light blue';
        }

        // Green and variations
        if ($g > 180 && $r < 100 && $b < 100) {
            return 'green';
        }

        // Yellow and variations
        if ($r > 180 && $g > 180 && $b < 100) {
            return 'yellow';
        }

        // Gray and variations
        if (abs($r - $g) < 30 && abs($g - $b) < 30 && abs($r - $b) < 30) {
            return 'gray';
        }

        // Fallback to RGB
        return sprintf('RGB(%d, %d, %d)', $r, $g, $b);
    }
}
