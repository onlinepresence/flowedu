<?php
/**
 * Student-facing checker: upload a photo to see if it meets the school's current
 * passport validation settings (same rules as real uploads). Settings are read-only.
 */

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/image_validation.php';

settings_invalidate_cache();
$settings = get_settings_by_category('image_validation');
$bgR = (int) ($settings['passport_bg_color_r'] ?? 255);
$bgG = (int) ($settings['passport_bg_color_g'] ?? 0);
$bgB = (int) ($settings['passport_bg_color_b'] ?? 0);
$tolerance = (int) ($settings['passport_tolerance'] ?? 120);
$matchPct = (int) ($settings['passport_match_percentage'] ?? 60);
$minW = (int) ($settings['passport_min_width'] ?? 300);
$minH = (int) ($settings['passport_min_height'] ?? 400);
$aspect = (string) ($settings['passport_aspect_ratio'] ?? '7:9');
$skipAspect = !empty($settings['passport_skip_ratio']);
$edgeDiv = (int) ($settings['passport_edge_sample_divisor'] ?? 100);

$results = null;
$error = null;
$imagePath = null;
/** Inline preview (data URI) — avoids a second HTTP request; file may be deleted after response. */
$imagePreviewSrc = null;
$imagePreviewOmitted = false;
$deleteAfterDisplay = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image_upload']) || $_FILES['image_upload']['error'] !== UPLOAD_ERR_OK) {
        $err = $_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            $error = 'Please choose an image to upload.';
        } elseif ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            $error = 'That file is too large. Try a smaller image.';
        } else {
            $error = 'Upload failed. Please try again.';
        }
    } else {
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $uploadDir = $docRoot . '/uploads/test_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('check_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['image_upload']['name']));
        $imagePath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $imagePath)) {
            $deleteAfterDisplay = true;
        } else {
            $error = 'Could not save the uploaded file.';
        }
    }

    if (!$error && $imagePath && file_exists($imagePath)) {
        // Single argument: use live system settings (same as student registration flow).
        $results = validate_passport_photo($imagePath);

        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo) {
            $results['image_info'] = [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'type' => image_type_to_mime_type($imageInfo[2]),
            ];
        }

        // Embed preview in the same response. A separate <img src="/uploads/..."> request often 404s
        // because register_shutdown_function deletes the temp file before the browser fetches it.
        $maxInline = 2_500_000;
        $raw = @file_get_contents($imagePath);
        $mime = $results['image_info']['type'] ?? 'image/jpeg';
        if ($raw !== false && strlen($raw) <= $maxInline) {
            $imagePreviewSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
        } elseif ($raw !== false) {
            $imagePreviewOmitted = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check your passport photo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 28px 24px;
            text-align: center;
        }

        .header h1 {
            font-size: 1.35rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .header p {
            opacity: 0.92;
            font-size: 0.9rem;
            line-height: 1.45;
        }

        .content { padding: 28px 24px; }

        .notice {
            background: #f0f4ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            color: #3730a3;
            line-height: 1.5;
        }

        .rules {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 24px;
        }

        .rules h2 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 14px;
        }

        .rules-grid {
            display: grid;
            gap: 12px;
        }

        .rule-row {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        .rule-row:last-child { border-bottom: 0; padding-bottom: 0; }

        .rule-label { color: #475569; font-weight: 600; }
        .rule-value { color: #0f172a; text-align: right; }

        .swatch {
            display: inline-block;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            vertical-align: middle;
            border: 1px solid #cbd5e1;
            margin-right: 8px;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #fff;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35);
        }

        .results {
            margin-top: 24px;
            padding: 18px;
            border-radius: 8px;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success {
            background: #ecfdf5;
            border: 2px solid #10b981;
            color: #065f46;
        }

        .error {
            background: #fef2f2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }

        .results h3 { margin-bottom: 10px; font-size: 1.05rem; }
        .results p { margin-bottom: 8px; line-height: 1.55; font-size: 0.95rem; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .info-item {
            background: rgba(255,255,255,0.6);
            padding: 10px 12px;
            border-radius: 6px;
            border-left: 3px solid #667eea;
            font-size: 0.88rem;
        }

        .info-item strong {
            display: block;
            margin-bottom: 4px;
            color: #4f46e5;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .image-preview { margin-top: 18px; text-align: center; }

        .image-preview img {
            max-width: 100%;
            max-height: 360px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Check your passport photo</h1>
            <p>See if your picture matches what the school expects before you submit it on your profile or registration.</p>
        </div>

        <div class="content">
            <div class="notice">
                The rules below are set by your school and cannot be changed here. Upload a photo to test it against the same checks used when you save your profile.
            </div>

            <div class="rules" aria-readonly="true">
                <h2>Current school settings (read-only)</h2>
                <div class="rules-grid">
                    <div class="rule-row">
                        <span class="rule-label">Background colour</span>
                        <span class="rule-value">
                            <span class="swatch" style="background-color: rgb(<?= (int) $bgR ?>, <?= (int) $bgG ?>, <?= (int) $bgB ?>);"></span>
                            RGB (<?= (int) $bgR ?>, <?= (int) $bgG ?>, <?= (int) $bgB ?>)
                        </span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">Colour tolerance</span>
                        <span class="rule-value"><?= (int) $tolerance ?> (0–441)</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">Background match on edges</span>
                        <span class="rule-value">At least <?= (int) $matchPct ?>% of sampled edge pixels</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">Minimum size</span>
                        <span class="rule-value"><?= (int) $minW ?> × <?= (int) $minH ?> pixels</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">Aspect ratio</span>
                        <span class="rule-value">
                            <?php if ($skipAspect): ?>
                                Not enforced (only minimum size is checked)
                            <?php else: ?>
                                About <strong><?= htmlspecialchars($aspect) ?></strong> (width : height)
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">Edge sampling (technical)</span>
                        <span class="rule-value">Divisor <?= (int) $edgeDiv ?> (higher = fewer samples)</span>
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="image_upload">Upload your photo</label>
                    <input id="image_upload" type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/jpg">
                </div>
                <button type="submit" class="submit-btn">Check photo</button>
            </form>

            <?php if ($error): ?>
                <div class="results error">
                    <h3>Something went wrong</h3>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($results): ?>
                <div class="results <?= $results['status'] ? 'success' : 'error' ?>">
                    <h3><?= $results['status'] ? 'This photo would be accepted' : 'This photo would not be accepted' ?></h3>
                    <p><?= htmlspecialchars($results['message']) ?></p>

                    <?php if (!empty($results['image_info'])): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Width</strong>
                                <?= (int) $results['image_info']['width'] ?> px
                            </div>
                            <div class="info-item">
                                <strong>Height</strong>
                                <?= (int) $results['image_info']['height'] ?> px
                            </div>
                            <div class="info-item">
                                <strong>Type</strong>
                                <?= htmlspecialchars($results['image_info']['type']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($imagePreviewSrc)): ?>
                        <div class="image-preview">
                            <p style="margin-bottom: 10px; font-weight: 600; color: inherit;">Your upload</p>
                            <img src="<?= htmlspecialchars($imagePreviewSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Uploaded test image">
                        </div>
                    <?php elseif (!empty($imagePreviewOmitted)): ?>
                        <p class="image-preview" style="margin-top: 18px; font-size: 0.9rem; opacity: 0.9;">
                            Preview is not shown for very large files (over about 2.5&nbsp;MB). Your photo was still checked using the same rules as a real upload.
                        </p>
                    <?php endif; ?>
                    <?php
                    if ($deleteAfterDisplay && is_string($imagePath) && $imagePath !== '') {
                        register_shutdown_function(static function () use ($imagePath): void {
                            if (file_exists($imagePath)) {
                                @unlink($imagePath);
                            }
                        });
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
