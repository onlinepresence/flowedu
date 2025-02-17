<?php
require_once relative_path("includes/components.php");

$title = 'Setup Halls'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <!-- Hall Name -->
        <?= input("text", "Hall Name", "name", required: true, attributes: placeholder("Name of hall")); ?>

        <!-- Hall Master -->
        <?= input("text", "Hall Master", "master", attributes: placeholder("Name of hall master")); ?>

        <!-- Hall cost -->
        <?= input("number", "Cost Per Head (GHC)", "cost", required: true, attributes: array_merge(
            placeholder("0.00"), attribute("step", 0.01)
        )); ?>

        <!-- payment life -->
        <?= select("period", "Duration of Cost", [
            "per_semester" => "Per Semester", "per_year" => "Per Year"
        ], true, required: true); ?>
        
    <?= form_body_end() ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Hall", "submit", "create_hall", "blue") ?>
    </div>
</form>

<!-- list of faculties -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            echo th("Name of Hall");
            echo th("House Master");
            echo th("Cost");
            echo th("Duration");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($halls = halls()): 
            foreach($halls as $hall):
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
                <?= td($hall["name"]); ?>
                <?= td($hall["master"] ? $hall["master"] : "Not Set"); ?>
                <?= td("GHC ".number_format($hall["cost"], 2)); ?>
                <?= td(format_hall_period($hall["period"])); ?>
                <?= td($action) ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No halls have been set yet", 5); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
