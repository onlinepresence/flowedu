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
            echo th("Head of Department");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($departments = departments(complete: true)):
            foreach($departments as $department) :
        ?>
            <?= tr_start(); ?>
                <?php 
                    $action = "
                        <div class=\"flex gap-2 items-center\">
                            <i class=\"fas fa-pen text-blue-500 hover:text-blue-600 cursor-pointer\" title=\"Edit\"></i>
                            <i class=\"fas fa-trash-can text-red-500 hover:text-red-600 cursor-pointer\" title=\"Delete\"></i>
                        </div>
                    ";
                ?>
                <?= td($department["name"]); ?>
                <?= td($department["faculty_id"] ? $department["faculty_name"] : "Not Set"); ?>
                <?= td($department["hod"] ? $department["lastname"].' '.$department["othernames"] : "Not Set"); ?>
                <?= td($action) ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No departments have been set yet", 4); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
