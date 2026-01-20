<?php
/**
 * Test file for validate_passport_photo function
 * 
 * Usage:
 * 1. Place an image file in the same directory or upload via form
 * 2. Access this file via browser
 * 3. Enter image path or upload an image
 * 4. Adjust settings (background color, tolerance) as needed
 */

require_once __DIR__ . '/includes/image_validation.php';

$results = null;
$error = null;
$imagePath = null;
$imageUrl = null;
$deleteAfterDisplay = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = $_POST['image_path'] ?? '';
    $targetR = (int)($_POST['target_r'] ?? 255);
    $targetG = (int)($_POST['target_g'] ?? 0);
    $targetB = (int)($_POST['target_b'] ?? 0);
    $tolerance = (int)($_POST['tolerance'] ?? 120);
    $minWidth = (int)($_POST['min_width'] ?? 300);
    $minHeight = (int)($_POST['min_height'] ?? 400);
    
    $targetColor = [$targetR, $targetG, $targetB];
    
    // Handle file upload
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/test_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = uniqid('test_') . '_' . basename($_FILES['image_upload']['name']);
        $imagePath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $imagePath)) {
            $deleteAfterDisplay = true;
        } else {
            $error = "Failed to upload image file.";
        }
    } else {
        // If a path is supplied, do not delete after display.
        $deleteAfterDisplay = false;
    }
    
    // Validate if image path is provided
    if (!$error && $imagePath) {
        if (file_exists($imagePath)) {
            $results = validate_passport_photo(
                $imagePath,
                $targetColor,
                $tolerance,
                $minWidth,
                $minHeight,
                false // skipRatioSize
            );
            
            // Get additional debug info
            $imageInfo = @getimagesize($imagePath);
            if ($imageInfo) {
                $results['image_info'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'type' => image_type_to_mime_type($imageInfo[2])
                ];
                
                // Get detected background color for debugging
                if (function_exists('get_detected_background_color')) {
                    $results['detected_color'] = get_detected_background_color($imagePath);
                }
            }

            // Prepare the image URL for <img src>, using url() helper
            // Make $imagePath relative to the root (assuming /uploads/test_images/)
            // Remove __DIR__ from local absolute path
            if (strpos($imagePath, __DIR__) === 0) {
                $imageUrl = url(str_replace(DIRECTORY_SEPARATOR, '/', ltrim(substr($imagePath, strlen(__DIR__)), '/')));
            } else {
                $imageUrl = url(ltrim($imagePath, '/'));
            }
        } else {
            $error = "Image file not found: " . htmlspecialchars($imagePath);
        }
    } else if (!$error && !$imagePath) {
        $error = "Please provide an image path or upload an image.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Photo Validation Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .color-inputs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .color-preview {
            width: 100%;
            height: 50px;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
            margin-top: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .results {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        
        .results h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .results p {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .image-preview {
            margin-top: 20px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        
        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: #667eea;
        }
        
        .preset-colors {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .preset-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .preset-btn:hover {
            border-color: #667eea;
            background: #f0f0ff;
        }
        
        .preset-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Passport Photo Validator</h1>
            <p>Test the validate_passport_photo function with your images</p>
        </div>
        
        <div class="content">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Upload Image</label>
                    <input type="file" name="image_upload" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>OR Enter Image Path</label>
                    <input type="text" name="image_path" placeholder="e.g., ./uploads/test.jpg" 
                           value="<?= htmlspecialchars($imagePath ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Target Background Color (RGB)</label>
                    <div class="color-inputs">
                        <input type="number" name="target_r" min="0" max="255" value="255" placeholder="R" required>
                        <input type="number" name="target_g" min="0" max="255" value="0" placeholder="G" required>
                        <input type="number" name="target_b" min="0" max="255" value="0" placeholder="B" required>
                    </div>
                    <div class="preset-colors">
                        <button type="button" class="preset-btn" onclick="setColor(255,0,0)">Red</button>
                        <button type="button" class="preset-btn" onclick="setColor(0,0,255)">Blue</button>
                        <button type="button" class="preset-btn" onclick="setColor(255,255,255)">White</button>
                        <button type="button" class="preset-btn" onclick="setColor(0,0,0)">Black</button>
                        <button type="button" class="preset-btn" onclick="setColor(34,139,34)">Green</button>
                    </div>
                    <div class="color-preview" id="colorPreview" style="background-color: rgb(255, 0, 0);"></div>
                </div>
                
                <div class="form-group">
                    <label>Color Tolerance (0-255)</label>
                    <input type="number" name="tolerance" min="0" max="441" value="120" required>
                    <small style="color: #666; font-size: 12px;">Higher = more lenient (default: 120)</small>
                </div>
                
                <div class="form-group">
                    <label>Minimum Dimensions</label>
                    <div class="color-inputs">
                        <input type="number" name="min_width" min="1" value="300" placeholder="Width" required>
                        <input type="number" name="min_height" min="1" value="400" placeholder="Height" required>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Validate Image</button>
            </form>
            
            <?php if ($error): ?>
                <div class="results error">
                    <h3>❌ Error</h3>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($results): ?>
                <div class="results <?= $results['status'] ? 'success' : 'error' ?>">
                    <h3><?= $results['status'] ? '✅ Validation Passed' : '❌ Validation Failed' ?></h3>
                    <p><strong>Message:</strong> <?= htmlspecialchars($results['message']) ?></p>
                    
                    <?php if (isset($results['image_info'])): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Width</strong>
                                <?= $results['image_info']['width'] ?>px
                            </div>
                            <div class="info-item">
                                <strong>Height</strong>
                                <?= $results['image_info']['height'] ?>px
                            </div>
                            <div class="info-item">
                                <strong>Type</strong>
                                <?= htmlspecialchars($results['image_info']['type']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    if (isset($imageUrl) && $imageUrl && $imagePath && file_exists($imagePath)): ?>
                        <div class="image-preview">
                            <h3 style="margin-bottom: 15px;">Image Preview</h3>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="Test Image">
                        </div>
                        <?php
                        // Immediately delete the uploaded file after displaying
                        if ($deleteAfterDisplay) {
                            // Output any necessary flushes for image delivery
                            flush();
                            ob_flush();
                            // Remove the file
                            $tryDeletePath = $imagePath;
                            // Delayed delete to ensure browser gets the image (works for most use cases)
                            register_shutdown_function(function() use ($tryDeletePath) {
                                if (file_exists($tryDeletePath)) {
                                    @unlink($tryDeletePath); // suppress error if any
                                }
                            });
                        }
                        ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update color preview
        document.querySelectorAll('input[name="target_r"], input[name="target_g"], input[name="target_b"]').forEach(input => {
            input.addEventListener('input', updateColorPreview);
        });
        
        function updateColorPreview() {
            const r = document.querySelector('input[name="target_r"]').value;
            const g = document.querySelector('input[name="target_g"]').value;
            const b = document.querySelector('input[name="target_b"]').value;
            document.getElementById('colorPreview').style.backgroundColor = `rgb(${r}, ${g}, ${b})`;
        }
        
        function setColor(r, g, b) {
            document.querySelector('input[name="target_r"]').value = r;
            document.querySelector('input[name="target_g"]').value = g;
            document.querySelector('input[name="target_b"]').value = b;
            updateColorPreview();
        }
        
        // Initial color preview update
        updateColorPreview();
    </script>
</body>
</html>
