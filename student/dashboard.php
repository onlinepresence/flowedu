<?php
require_once relative_path("includes/components.php");

$title = 'Dashboard'; // Set the page title
$user = user();
$cgpa = "0.00";

// Start output buffering to capture the content
ob_start();
?>

 <!-- cards -->
 <?= card_container_start() ?>
    <?= dashboard_card_btn("Current Level", $user["graduated"] ? "Graduated" : "Level ".$user["current_year"], "fas fa-user-graduate") ?>
    <?= dashboard_card_btn("CGPA", $cgpa, "fas fa-star", "blue") ?>
    <?= dashboard_card_btn("Outstanding Fees", "GHC 0.00", "fas fa-wallet", "green") ?>
<?= card_container_end() ?>

<!-- course registration table -->
<?= table_start() ?>
    <?= thead_start() ?>
        <?= th("Course Code") ?>
        <?= th("Course Name") ?>
        <?= th("Credit Hours") ?>
    <?= thead_end() ?>

    <?= tbody_start() ?>
        <?= tr_start() ?>
            <?= td_empty("Course Registration has not started yet", 3) ?>
        <?= tr_end() ?>
    <?= tbody_end() ?>
<?= table_end() ?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
