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
    function validate_passport_photo($imagePath, $target_bg_color = [255, 0, 0], $bgTolerance = 50, $minWidth = 300, $minHeight = 400, $skipRatioSize = true)
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
     * @param int $tolerance Tolerance level for color matching (default is 50).
     * @return bool True if the background color matches, false otherwise.
     */
    function is_background_color_match($imagePath, $targetColor = [255, 0, 0], $tolerance = 120){
        $image = imagecreatefromstring(file_get_contents($imagePath));
        if (!$image) {
            return false;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        $samples = [];

        // --- Sample top and bottom edges
        for ($x = 0; $x < $width; $x++) {
            foreach ([0, $height - 1] as $y) {
                $samples[] = getRGBAt($image, $x, $y);
            }
        }

        // --- Sample left and right edges
        for ($y = 0; $y < $height; $y++) {
            foreach ([0, $width - 1] as $x) {
                $samples[] = getRGBAt($image, $x, $y);
            }
        }

        imagedestroy($image);

        if (empty($samples)) {
            return false;
        }

        // --- Compute average color of sampled edge pixels
        $avgColor = [
            array_sum(array_column($samples, 0)) / count($samples),
            array_sum(array_column($samples, 1)) / count($samples),
            array_sum(array_column($samples, 2)) / count($samples),
        ];

        // --- Check closeness with Euclidean distance
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
    ?>
