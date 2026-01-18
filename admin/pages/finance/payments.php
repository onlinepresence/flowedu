<?php
require_once relative_path("includes/components.php");
$title = 'Payment Records';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-money-check-alt mr-2"></i>Payment Records
        </h3>
        
        <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= input("text", "Search Student", "search", "", false, array_merge(attribute("id", "search"), data_attr("filter", "search"))) ?>
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(attribute("id", "filter-program"), data_attr("filter", "program"))) ?>
            <?php $status_options = [["id" => "", "text" => "All Status"], ["id" => "paid", "text" => "Paid"], ["id" => "pending", "text" => "Pending"], ["id" => "partial", "text" => "Partial"]]; ?>
            <?= select("filter_status", "Status", $status_options, "All Status", attributes: array_merge(attribute("id", "filter-status"), data_attr("filter", "status"))) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Student") ?>
                <?= th("Index Number") ?>
                <?= th("Amount") ?>
                <?= th("Payment Date") ?>
                <?= th("Payment Method") ?>
                <?= th("Reference") ?>
                <?= th("Status") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading payment records...", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
