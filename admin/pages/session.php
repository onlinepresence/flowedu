<?php
require_once relative_path("includes/components.php");

$title = 'Academic Term / Session'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<p>A Page to filter details about a specified academic term</p>
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
require relative_path('layouts/auth.php');
