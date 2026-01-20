<?php

    /**
     * Validate if an image qualifies as a passport photo.
     *
     * @param string $imagePath Path to the image file.
     * @param array $target_bg_color Expected background color in RGB (default is red).
     * @param int $bgTolerance Tolerance level for background color matching (default is 50).
     * @param int $minWidth Minimum image width (default is 300).
     * @param int $minHeight Minimum image height (default is 400).
     * @param bool $skipRatioSize Skips the aspect ratio check
     * @return array Status and message indicating the result of the validation.
     */
    function validate_passport_photo($imagePath, $target_bg_color = [255, 0, 0], $bgTolerance = 120, $minWidth = 300, $minHeight = 400, $skipRatioSize = true)
    {
        if (!file_exists($imagePath)) {
            return ['status' => false, 'message' => 'Image file not found.'];
        }

        [$width, $height, $type] = getimagesize($imagePath);

        // Check size
        if ($skipRatioSize && !is_image_size_acceptable($width, $height, $minWidth, $minHeight)) {
            return ['status' => false, 'message' => "Image too small. Minimum required size is {$minWidth}x{$minHeight} pixels."];
        }

        // Check aspect ratio
        if (!$skipRatioSize && !is_passport_aspect_ratio($width, $height)) {
            return ['status' => false, 'message' => "Invalid aspect ratio. Expected ratio is close to 7:9 (e.g. 350x450)."];
        }

        // Check background color
        if (!is_background_color_match($imagePath, $target_bg_color, $bgTolerance)) {
            return ['status' => false, 'message' => "Background color does not match expected color. Please use a solid background (e.g. red)."];
        }

        return ['status' => true, 'message' => 'Image is valid as a passport photo.'];
    }

    /**
     * Check if the image size meets the minimum width and height requirements.
     *
     * @param int $width The width of the image.
     * @param int $height The height of the image.
     * @param int $minWidth Minimum allowed width.
     * @param int $minHeight Minimum allowed height.
     * @return bool True if the image size is acceptable, false otherwise.
     */
    function is_image_size_acceptable($width, $height, $minWidth = 300, $minHeight = 400)
    {
        return $width >= $minWidth && $height >= $minHeight;
    }

    /**
     * Check if the image's aspect ratio is close to the standard 7:9 ratio.
     *
     * @param int $width The width of the image.
     * @param int $height The height of the image.
     * @param float $tolerance Allowed tolerance for aspect ratio (default is 0.05).
     * @return bool True if the aspect ratio is acceptable, false otherwise.
     */
    function is_passport_aspect_ratio($width, $height, $tolerance = 0.05)
    {
        $actualRatio = $width / $height;
        $expectedRatio = 7 / 9;

        return abs($actualRatio - $expectedRatio) <= $tolerance;
    }

    /**
     * Check if the background color of the image matches the expected color.
     *
     * @param string $imagePath Path to the image file.
     * @param array $targetColor RGB color of the target background (default is red).
     * @param int $tolerance Tolerance level for color matching (default is 120).
     * @return bool True if the background color matches, false otherwise.
     */
    function is_background_color_match($imagePath, $targetColor = [255, 0, 0], $tolerance = 120){
        if (!file_exists($imagePath)) {
            return false;
        }

        // Determine image type and load accordingly
        $imageInfo = @getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }

        $width  = $imageInfo[0];
        $height = $imageInfo[1];
        $type   = $imageInfo[2];

        // Load image based on type
        $image = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($imagePath);
                // Preserve transparency for PNG
                if ($image) {
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($imagePath);
                }
                break;
            default:
                // Fallback to imagecreatefromstring
                $imageData = @file_get_contents($imagePath);
                if ($imageData) {
                    $image = @imagecreatefromstring($imageData);
                }
                break;
        }

        if (!$image) {
            return false;
        }

        // Sample strategy: Focus on corners and edges where background is most likely
        // Use multiple sampling methods and take the best match
        
        $samples = [];
        
        // Method 1: Sample corners extensively (most reliable for background)
        $cornerSize = min(20, floor($width / 10), floor($height / 10)); // Sample a small area around each corner
        $corners = [
            [0, 0],                           // Top-left
            [$width - 1, 0],                  // Top-right
            [0, $height - 1],                 // Bottom-left
            [$width - 1, $height - 1]         // Bottom-right
        ];
        
        foreach ($corners as $corner) {
            $cx = $corner[0];
            $cy = $corner[1];
            // Sample a small square around the corner
            for ($dx = 0; $dx < $cornerSize && $cx + $dx < $width; $dx++) {
                for ($dy = 0; $dy < $cornerSize && $cy + $dy < $height; $dy++) {
                    $samples[] = getRGBAt($image, $cx + $dx, $cy + $dy);
                }
            }
        }
        
        // Method 2: Sample edges with more density
        $edgeStep = max(1, floor(min($width, $height) / 100)); // Sample more pixels for better accuracy
        
        // Top and bottom edges (full width)
        for ($x = 0; $x < $width; $x += $edgeStep) {
            $samples[] = getRGBAt($image, $x, 0);
            $samples[] = getRGBAt($image, $x, $height - 1);
        }
        
        // Left and right edges (full height)
        for ($y = 0; $y < $height; $y += $edgeStep) {
            $samples[] = getRGBAt($image, 0, $y);
            $samples[] = getRGBAt($image, $width - 1, $y);
        }

        imagedestroy($image);

        if (empty($samples)) {
            return false;
        }

        // Strategy: Check if a significant percentage of samples match the target color
        // This is more robust than averaging, as averaging can be diluted by subject pixels
        $matchingSamples = 0;
        $totalSamples = count($samples);
        
        // Use a slightly more lenient tolerance for individual pixel matching
        $pixelTolerance = $tolerance * 1.2; // 20% more lenient for individual pixels
        
        foreach ($samples as $sample) {
            if (is_color_close_euclidean($sample, $targetColor, $pixelTolerance)) {
                $matchingSamples++;
            }
        }
        
        // Require at least 60% of samples to match (background should dominate edges)
        $matchPercentage = ($matchingSamples / $totalSamples) * 100;
        $requiredMatchPercentage = 60;
        
        if ($matchPercentage >= $requiredMatchPercentage) {
            return true;
        }
        
        // Fallback: Also check average color (in case background is uniform but slightly off)
        $avgColor = [
            (int) round(array_sum(array_column($samples, 0)) / $totalSamples),
            (int) round(array_sum(array_column($samples, 1)) / $totalSamples),
            (int) round(array_sum(array_column($samples, 2)) / $totalSamples),
        ];
        
        // Use the average check as a secondary validation
        return is_color_close_euclidean($avgColor, $targetColor, $tolerance);
    }

    /**
     * Get the RGB values of a pixel at a specific coordinate.
     *
     * @param GdImage $image The image resource.
     * @param int $x The x-coordinate of the pixel.
     * @param int $y The y-coordinate of the pixel.
     * @return array RGB values of the pixel.
     */
    function getRGBAt($image, $x, $y)
    {
        $index = imagecolorat($image, $x, $y);
        $colors = imagecolorsforindex($image, $index);
        return [$colors['red'], $colors['green'], $colors['blue']];
    }

    /**
     * Compare two RGB colors to check if they are close enough based on a given tolerance.
     *
     * @param array $color1 First RGB color array.
     * @param array $color2 Second RGB color array.
     * @param int $tolerance The tolerance level for color matching (default is 50).
     * @return bool True if the colors are close, false otherwise.
     */
    function is_color_close($color1, $color2, $tolerance = 50)
    {
        return (
            abs($color1[0] - $color2[0]) <= $tolerance &&
            abs($color1[1] - $color2[1]) <= $tolerance &&
            abs($color1[2] - $color2[2]) <= $tolerance
        );
    }

    /**
     * Compare two RGB colors using Euclidean distance in 3D color space.
     * This is more accurate than per-channel comparison as it considers the overall color similarity.
     *
     * @param array $color1 First RGB color array [R, G, B].
     * @param array $color2 Second RGB color array [R, G, B].
     * @param float $tolerance Maximum Euclidean distance allowed (default is 120).
     * @return bool True if the Euclidean distance between colors is within tolerance, false otherwise.
     */
    function is_color_close_euclidean($color1, $color2, $tolerance = 120)
    {
        // Calculate Euclidean distance in 3D RGB space
        $distance = sqrt(
            pow($color1[0] - $color2[0], 2) +
            pow($color1[1] - $color2[1], 2) +
            pow($color1[2] - $color2[2], 2)
        );

        return $distance <= $tolerance;
    }
    ?>
