<?php
require_once relative_path("includes/components.php");

$title = 'Activate School'; // Set the page title
$school = school();
$departments = departments();
$programs = programs();
$halls = halls();

// Start output buffering to capture the content
ob_start();
?>
<div class="border py-8 space-y-4">
    <?php if($school && $departments && $programs && $halls): ?>
    <p class="text-center dark:text-white">By clicking the button below, you agree that the system should be <?= $school["ready"] ? "deactivated" : "activated" ?></p>
    <form action="<?= url("admin/submit.php") ?>" method="POST">
        <input type="hidden" name="ready" value="<?= $school["ready"] ? 0 : 1 ?>">
        <div class="grid grid-cols-1 items-center m-auto max-w-48">
            <?= button("submit", $school["ready"] ? "Deactivate" : "Activate", "submit", "change_school_status", $school["ready"] ? "red" : "blue") ?>
        </div>
    </form>
    <?php else: ?>
        <p class="ml-4 <?= $school ? "text-green-600" : "text-red-600" ?>"><i class="fas <?= $school ? "fas fa-check" : "fas fa-exclamation" ?>"></i> Provided school details</p>
        <p class="ml-4 <?= $departments ? "text-green-600" : "text-red-600" ?>"><i class="fas <?= $departments ? "fas fa-check" : "fas fa-exclamation" ?>"></i> Provided at least one department</p>
        <p class="ml-4 <?= $programs ? "text-green-600" : "text-red-600" ?>"><i class="fas <?= $programs ? "fas fa-check" : "fas fa-exclamation" ?>"></i> Provided at least one program</p>
        <p class="ml-4 <?= $halls ? "text-green-600" : "text-red-600" ?>"><i class="fas <?= $halls ? "fas fa-check" : "fas fa-exclamation" ?>"></i> Provided at least one hall</p>
    <?php endif; ?>
</div>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
