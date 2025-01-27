<?php
require_once relative_path("includes/components.php");

$title = 'Setup Halls'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
