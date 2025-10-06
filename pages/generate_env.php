<?php
require_once "includes/functions.php";
require_once "includes/components.php";
session_start();

$envPath = $_SERVER["DOCUMENT_ROOT"].'/.env'; // Adjust path as needed relative to this file
$envValues = [];
$message = ''; // To store success/error messages

// Load existing .env values
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envValues[$key] = $value;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = array_keys($_POST);
    $envContent = "";
    foreach ($keys as $key) {
        $value = $_POST[$key] ?? '';
        $envContent .= "$key=$value\n";
    }
    if (file_put_contents($envPath, $envContent)) {
        $message = alert("ENV file saved successfully", "success");
        // Optionally, reload $envValues after saving
        $envValues = $_POST;
    } else {
        $message = alert("Error saving .env file", "error");
    }
}

$title = 'Setup Environment Variables';

// Start output buffering to capture the content
ob_start();
?>
<div class="container">
    <h2>Environment Setup</h2>
    <?php echo $message; ?>
    <form method="POST" class="space-y-4">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
                echo input('text', 'APP_ENV', 'APP_ENV', $envValues['APP_ENV'] ?? 'local');
                echo input('text', 'APP_DEBUG', 'APP_DEBUG', $envValues['APP_DEBUG'] ?? 'true');
                echo input('text', 'APP_URL', 'APP_URL', $envValues['APP_URL'] ?? $_SERVER["HTTP_HOST"]);
                echo input('text', 'DB_HOST', 'DB_HOST', $envValues['DB_HOST'] ?? 'localhost');
                echo input('text', 'DB_USERNAME', 'DB_USERNAME', $envValues['DB_USERNAME'] ?? 'root');
                echo input('text', 'DB_PASSWORD', 'DB_PASSWORD', $envValues['DB_PASSWORD'] ?? '');
                echo input('text', 'DB_NAME', 'DB_NAME', $envValues['DB_NAME'] ?? '');
                echo input('text', 'MAIL_HOST', 'MAIL_HOST', $envValues['MAIL_HOST'] ?? '');
                echo input('text', 'MAIL_USERNAME', 'MAIL_USERNAME', $envValues['MAIL_USERNAME'] ?? '');
                echo input('text', 'MAIL_PASSWORD', 'MAIL_PASSWORD', $envValues['MAIL_PASSWORD'] ?? '');
                echo input('text', 'SERVER_DOWN', 'SERVER_DOWN', $envValues['SERVER_UP'] ?? 'false');
                echo input('text', 'SYSTEM_PASSWORD', 'SYSTEM_PASSWORD', $envValues['SYSTEM_PASSWORD'] ?? '');
            ?>
        </div>

        <div class="flex gap-4 max-w-sm">
            <?= button('submit', 'Save .env'); ?>
            <?php if (file_exists($envPath)) {
                    if(!is_null(user())){
                        echo button('button', 'Go to Dashboard', attributes:
                            attribute("onclick", "location.replace('/admin/dashboard')")
                        );
                    }else{
                        echo button('button', 'Go to Login', attributes:
                            attribute("onclick", "location.href='/'")
                        );
                    }
                }
            ?>
        </div>
    </form>
</div>
<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require 'layouts/blank.php';
