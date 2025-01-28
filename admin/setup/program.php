<?php
require_once relative_path("includes/components.php");

$title = 'Setup Programs'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <!-- Program Name -->
        <?= input("text", "Program Name", "name", required: true, attributes: placeholder("Name of the program")); ?>

        <!-- Program price -->
        <?= input("text", "Program Fee", "cost", required: true, attributes: array_merge(placeholder("0.00"), attribute("step", 0.01))); ?>
        
        <!-- Program certificate -->
        <?= input("text", "Program Certification", "certificate", required: true, attributes: placeholder("Eg. Bachelor of Education (B.Ed)")); ?>

        <?php 
            $departments = departments();
            $departments = $departments ? pluck($department, "id", "name") : ["" => "No Departments created"];
            echo select("department_id", "Course's Department", $departments, required: true)
        ?>
        
    <?= form_body_end() ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Program", "submit", "create_program", "blue") ?>
    </div>
</form>

<!-- list of faculties -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            echo th("Name of Program");
            echo th("Certification");
            echo th("Cost of Program");
            echo th("Department");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($programs = programs()): ?>
        <?php else: echo td_empty("No programs have been set yet", 2); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
