<?php
require_once relative_path("includes/components.php");

$title = 'Setup Departments'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <!-- Department Name -->
        <?= input("text", "Department Name", "name", required: true, attributes: placeholder("Name of the department")); ?>

        <!-- Faculty Dropdown -->
        <?php
            // Fetch faculties from the database
            $faculties = faculties();
            $faculty_options = $faculties ? pluck($faculties, "id", "name") : [];
            echo select("faculty_id", "Department Faculty", $faculty_options, true);
        ?>
    <?= form_body_end() ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Department", "submit", "create_department", "blue") ?>
    </div>
</form>

<!-- List of departments -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            echo th("Name of Department");
            echo th("Faculty");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($departments = departments()): ?>
        <?php else: echo td_empty("No departments have been set yet", 3); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
