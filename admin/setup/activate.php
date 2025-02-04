<?php
require_once relative_path("includes/components.php");

$title = 'Activate School'; // Set the page title
$school = school();

// Start output buffering to capture the content
ob_start();
?>
<div class="border py-8 space-y-4">
    <p class="text-center dark:text-white">By clicking the button below, you agree that the system should be <?= $school["ready"] ? "deactivated" : "activated" ?></p>
    <form action="<?= url("admin/submit.php") ?>" method="POST">
        <input type="hidden" name="ready" value="<?= $school["ready"] ? 0 : 1 ?>">
        <div class="grid grid-cols-1 items-center m-auto max-w-48">
            <?= button("submit", $school["ready"] ? "Deactivate" : "Activate", "submit", "change_school_status", $school["ready"] ? "red" : "blue") ?>
        </div>
    </form>
</div>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
