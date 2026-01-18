<?php
require_once relative_path("includes/components.php");
$title = 'Outstanding Fees';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-exclamation-triangle mr-2"></i>Outstanding Fees
        </h3>
        
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?= input("text", "Search Student", "search", "", false, array_merge(attribute("id", "search"), data_attr("filter", "search"))) ?>
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(attribute("id", "filter-program"), data_attr("filter", "program"))) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Student") ?>
                <?= th("Index Number") ?>
                <?= th("Program") ?>
                <?= th("Total Fee") ?>
                <?= th("Paid") ?>
                <?= th("Outstanding") ?>
                <?= th("Due Date") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading outstanding fees...", 8) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
