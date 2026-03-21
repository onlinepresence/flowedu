<?php
require_once relative_path('includes/components.php');
require_once relative_path('includes/settings_functions.php');

$title = 'Image Validation';
$page_title = 'Passport photo validation';

// Fresh read from DB (avoid stale session cache); admin edits expect current values.
settings_invalidate_cache();
$s = get_settings_by_category('image_validation');
$v = static function (string $key, $default = '') use ($s) {
    return $s[$key] ?? $default;
};

$r = (int) $v('passport_bg_color_r', 255);
$g = (int) $v('passport_bg_color_g', 0);
$b = (int) $v('passport_bg_color_b', 0);
$skipRatio = filter_var($v('passport_skip_ratio', true), FILTER_VALIDATE_BOOLEAN);

$aspectRatioDefault = (string) $v('passport_aspect_ratio', '7:9');
$aspectRatios = [
    '7:9' => [7, 9],
    '3:4' => [3, 4],
    '1:1' => [1, 1]
];
[$arW, $arH] = $aspectRatios[$aspectRatioDefault] ?? [7, 9];

// Empty strings in old_input override DB defaults inside input()/select() (old() returns '').
$ivOldKeys = [
    'passport_bg_color_r', 'passport_bg_color_g', 'passport_bg_color_b',
    'passport_tolerance', 'passport_match_percentage',
    'passport_min_width', 'passport_min_height',
    'passport_aspect_ratio', 'passport_edge_sample_divisor',
];
foreach ($ivOldKeys as $k) {
    if (isset($_SESSION['old_input'][$k]) && $_SESSION['old_input'][$k] === '') {
        unset($_SESSION['old_input'][$k]);
    }
}

$submitUrl = url('admin/submit.php');

ob_start();
?>
<script>
function imagePreview() {
    return {
        r: <?=(int) $r?>,
        g: <?=(int) $g?>,
        b: <?=(int) $b?>,
        aspectRatio: '<?= htmlspecialchars($aspectRatioDefault, ENT_QUOTES, 'UTF-8') ?>',
        aspectRatios: {
            '7:9': [7,9],
            '3:4': [3,4],
            '1:1': [1,1]
        },
        get aspectStyle() {
            let w = 162;
            let ratioArr = this.aspectRatios[this.aspectRatio] || [7,9];
            let h = Math.round(w * (ratioArr[1] / ratioArr[0]));
            return `width:${w}px; height:${h}px; background-color:rgb(${this.r},${this.g},${this.b});`;
        },
        setPreset(r, g, b) {
            this.r = r;
            this.g = g;
            this.b = b;
        },
        setPresetAndInputs(r, g, b) {
            this.setPreset(r, g, b);
            document.getElementById('passport_bg_color_r').value = r;
            document.getElementById('passport_bg_color_g').value = g;
            document.getElementById('passport_bg_color_b').value = b;
            document.getElementById('passport_bg_color_r').dispatchEvent(new Event('input', { bubbles: true }));
            document.getElementById('passport_bg_color_g').dispatchEvent(new Event('input', { bubbles: true }));
            document.getElementById('passport_bg_color_b').dispatchEvent(new Event('input', { bubbles: true }));
        },
        updateAspectRatio() {},
        initPreview() {
            let self = this;
            document.getElementById('passport_bg_color_r').addEventListener('input', function() { self.r = parseInt(this.value, 10) || 0; });
            document.getElementById('passport_bg_color_g').addEventListener('input', function() { self.g = parseInt(this.value, 10) || 0; });
            document.getElementById('passport_bg_color_b').addEventListener('input', function() { self.b = parseInt(this.value, 10) || 0; });
        }
    }
}
</script>

<div class="container px-6 mx-auto grid">
    <?= information_bar(
        'These settings apply when students upload passport-style photos. Changes take effect on the next validation.',
        'info',
        false,
        attribute('class', 'mb-6')
    ) ?>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Preview Column -->
        <div class="w-full lg:w-1/3 shrink-0">
            <div 
                x-data="imagePreview()" 
                x-init="initPreview()" 
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md p-6 shadow-sm flex flex-col items-center mb-8 lg:mb-0"
            >
                <div class="mb-4 font-semibold text-lg text-gray-700 dark:text-gray-200">Preview</div>
                <!-- Preview Card - aspect ratio and color bound to alpine values -->
                <div
                    id="passport-color-preview"
                    :style="aspectStyle"
                    class="flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-inner mb-4 transition-all duration-200"
                >
                    <span class="text-xs text-gray-500 dark:text-gray-400">Background</span>
                </div>
                <div class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                    Adjust RGB values or select a preset to see the preview update.
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $presets = [
                        'Red'   => [255, 0, 0],
                        'Blue'  => [0, 0, 255],
                        'White' => [255, 255, 255],
                        'Black' => [0, 0, 0],
                        'Green' => [0, 128, 0],
                    ];
                    foreach ($presets as $label => $rgb) {
                        echo '<button type="button" class="px-3 py-1 text-xs font-medium rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 preset-rgb" 
                            @click="setPresetAndInputs(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ')">' . htmlspecialchars($label) . '</button>';
                    }
                    ?>
                </div>
                <div class="mt-6 text-xs text-gray-400 text-center">
                    <span class="block mb-1">Current RGB: 
                        <span id="preview-rgb-label" class="font-mono text-gray-800 dark:text-gray-100" x-text="r + ', ' + g + ', ' + b"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Settings Column -->
        <div class="w-full lg:w-2/3">
            <form id="image-validation-form" class="space-y-6">
                <?= input('hidden', '', 'submit', 'update_image_validation_settings') ?>
                <?= input('hidden', '', 'response_type', 'json') ?>

                <?= fieldset_start(attribute('class', 'dark:border-gray-600')) ?>
                <?= fieldset_legend('Background color (RGB)') ?>
                <div class="mb-2 text-xs text-gray-600 dark:text-gray-400">
                    Increasing a value makes that color more prominent in the required background, and decreasing makes it less. Use the preview (at left on large screens, above on small screens) to see the color you are selecting. Note that this will be the primary color for the background.
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <?= input(
                        'number',
                        'Red (0–255)',
                        'passport_bg_color_r',
                        (string) $r,
                        true,
                        [
                            'min' => 0,
                            'max' => 255,
                            'id' => 'passport_bg_color_r',
                            'x-model' => 'r'
                        ]
                    ) ?>
                    <?= input(
                        'number',
                        'Green (0–255)',
                        'passport_bg_color_g',
                        (string) $g,
                        true,
                        [
                            'min' => 0,
                            'max' => 255,
                            'id' => 'passport_bg_color_g',
                            'x-model' => 'g'
                        ]
                    ) ?>
                    <?= input(
                        'number',
                        'Blue (0–255)',
                        'passport_bg_color_b',
                        (string) $b,
                        true,
                        [
                            'min' => 0,
                            'max' => 255,
                            'id' => 'passport_bg_color_b',
                            'x-model' => 'b'
                        ]
                    ) ?>
                </div>
                <?= fieldset_end() ?>

                <?= fieldset_start(attribute('class', 'dark:border-gray-600')) ?>
                <?= fieldset_legend('Color matching') ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?= input_h(
                        'number',
                        'Tolerance (0–441)',
                        'passport_tolerance',
                        (string) (int) $v('passport_tolerance', 120),
                        true,
                        'Higher values accept more color differences from the background. 0 is strict.',
                        [
                            'min' => 0,
                            'max' => 441
                        ]
                    ) ?>
                    <?= input_h(
                        'number',
                        'Match threshold (%)',
                        'passport_match_percentage',
                        (string) (int) $v('passport_match_percentage', 60),
                        true,
                        'Minimum percent of background pixels that must match the color.',
                        [
                            'min' => 0,
                            'max' => 100
                        ]
                    ) ?>
                </div>
                <?= fieldset_end() ?>

                <?= fieldset_start(attribute('class', 'dark:border-gray-600')) ?>
                <?= fieldset_legend('Minimum dimensions (pixels)') ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?= input_h(
                        'number',
                        'Minimum width',
                        'passport_min_width',
                        (string) (int) $v('passport_min_width', 300),
                        true,
                        'The smallest width allowed for uploaded images.',
                        ['min' => 1]
                    ) ?>
                    <?= input_h(
                        'number',
                        'Minimum height',
                        'passport_min_height',
                        (string) (int) $v('passport_min_height', 400),
                        true,
                        'The smallest height allowed for uploaded images.',
                        ['min' => 1]
                    ) ?>
                </div>
                <?= fieldset_end() ?>

                <?= fieldset_start(attribute('class', 'dark:border-gray-600')) ?>
                <?= fieldset_legend('Aspect ratio') ?>
                <div class="space-y-3">
                    <?= select(
                        'passport_aspect_ratio',                                    // $name
                        'Required ratio (when check is enabled)',                  // $text (label)
                        [                                                         // $options (array or list, processed based on option keys)
                            ['value' => '7:9', 'text' => '7:9 (passport)'],
                            ['value' => '3:4', 'text' => '3:4'],
                            ['value' => '1:1', 'text' => '1:1'],
                        ],
                        false,                                                    // $nullable
                        false,                                                    // $multiple
                        ['value' => 'value', 'text' => 'text'],                   // $keys
                        true,                                                     // $required
                        (string) $v('passport_aspect_ratio', '7:9'), // DB value; old() skipped — select() still applies old() internally
                        [
                            'x-model' => 'aspectRatio',
                            '@change' => 'updateAspectRatio()',
                        ]
                    ) ?>
                    <div>
                        <?= input('hidden', '', 'passport_skip_ratio', '0') ?>
                        <?= checkbox(
                            'passport_skip_ratio',
                            '1',
                            'Skip aspect ratio check (only enforce minimum size)',
                            false,
                            array_merge(attribute('id', 'passport_skip_ratio'), $skipRatio ? attribute('checked', 'checked') : [])
                        ) ?>
                    </div>
                </div>
                <?= fieldset_end() ?>

                <?= fieldset_start(attribute('class', 'dark:border-gray-600')) ?>
                <?= fieldset_legend('Advanced') ?>
                <?= input(
                    'number',
                    'Edge sample divisor (10–500)',
                    'passport_edge_sample_divisor',
                    (string) (int) $v('passport_edge_sample_divisor', 100),
                    true,
                    ['min' => 10, 'max' => 500]
                ) ?>
                <?= information_bar('Higher values sample fewer edge pixels (default 100 matches previous behavior).', 'info', false, attribute('class', 'mt-2')) ?>
                <?= fieldset_end() ?>

                <div class="flex justify-end">
                    <?= button('submit', 'Save settings', '', '', 'purple', array_merge(
                        attribute('id', 'image-validation-save')
                    )) ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = <<<HTML
<script>
$(function () {
    $('#image-validation-form').on('submit', function (e) {
        e.preventDefault();
        var \$form = $(this);
        $.ajax({
            url: '{$submitUrl}',
            method: 'POST',
            data: \$form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response && response.status) {
                    alert_box(response.data && response.data.message ? response.data.message : 'Settings saved', 'success');
                    \$form.find('input').removeClass('border-red-600 dark:border-red-400');
                    \$form.find('.error-span').remove();
                } else {
                    if (response && response.errors) {
                        display_form_errors(response.errors, \$form);
                    } else {
                        alert_box('Could not save settings', 'danger');
                    }
                }
            },
            error: function (xhr) {
                var msg = 'System error occurred';
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.system_message) {
                    msg = xhr.responseJSON.errors.system_message;
                }
                alert_box(msg, 'danger');
            }
        });
    });
});
</script>
HTML;
require relative_path('layouts/auth.php');
