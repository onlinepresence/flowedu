<?php

/**
 * Legacy admin "image validation" settings (includes/image_validation.php + get_setting('image_validation.*')).
 * Env names support both COLLEGE_* (existing) and IMAGE_VALIDATION_* aliases.
 */
return [

    'passport' => [
        'enabled' => filter_var(
            env('IMAGE_VALIDATION_ENABLED', env('COLLEGE_PASSPORT_VALIDATION_ENABLED', true)),
            FILTER_VALIDATE_BOOL
        ),
        'min_width' => (int) env('IMAGE_VALIDATION_PASSPORT_MIN_WIDTH', env('COLLEGE_PASSPORT_MIN_WIDTH', 300)),
        'min_height' => (int) env('IMAGE_VALIDATION_PASSPORT_MIN_HEIGHT', env('COLLEGE_PASSPORT_MIN_HEIGHT', 400)),
        'skip_ratio' => filter_var(
            env('IMAGE_VALIDATION_PASSPORT_SKIP_RATIO', env('COLLEGE_PASSPORT_SKIP_RATIO', true)),
            FILTER_VALIDATE_BOOL
        ),
        'aspect_ratio' => env('IMAGE_VALIDATION_PASSPORT_ASPECT', env('COLLEGE_PASSPORT_ASPECT_RATIO', '7:9')),
        'aspect_tolerance' => (float) env(
            'IMAGE_VALIDATION_PASSPORT_ASPECT_TOLERANCE',
            env('COLLEGE_PASSPORT_ASPECT_TOLERANCE', 0.05)
        ),
        'bg_color' => [
            'r' => (int) env('IMAGE_VALIDATION_PASSPORT_BG_R', env('COLLEGE_PASSPORT_BG_R', 255)),
            'g' => (int) env('IMAGE_VALIDATION_PASSPORT_BG_G', env('COLLEGE_PASSPORT_BG_G', 0)),
            'b' => (int) env('IMAGE_VALIDATION_PASSPORT_BG_B', env('COLLEGE_PASSPORT_BG_B', 0)),
        ],
        'bg_tolerance' => (int) env(
            'IMAGE_VALIDATION_PASSPORT_BG_TOLERANCE',
            env('COLLEGE_PASSPORT_BG_TOLERANCE', 120)
        ),
        'edge_match_percent' => (int) env(
            'IMAGE_VALIDATION_PASSPORT_MATCH_PERCENT',
            env('COLLEGE_PASSPORT_EDGE_MATCH_PERCENT', 60)
        ),
        'edge_sample_divisor' => (int) env(
            'IMAGE_VALIDATION_PASSPORT_EDGE_DIVISOR',
            env('COLLEGE_PASSPORT_EDGE_SAMPLE_DIVISOR', 100)
        ),
    ],

];
