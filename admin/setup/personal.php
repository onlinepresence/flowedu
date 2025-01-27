<?php
require_once relative_path("includes/components.php");

$title = 'Personal Information'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<p>This is the personal file</p>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
