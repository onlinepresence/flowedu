<?php
require_once relative_path("includes/components.php");
$title = 'Scholarships / Grants';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-gift mr-2"></i>Manage Scholarships/Grants
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST">
            <?= input("hidden", "", "request_type", "add_scholarship") ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= input("text", "Scholarship Name", "name", "", true) ?>
                <?= input("number", "Amount", "amount", "", true) ?>
                <?= textarea("description", "Description", "", false, attribute("rows", "3")) ?>
                <?= select("type", "Type", [["id" => "scholarship", "text" => "Scholarship"], ["id" => "grant", "text" => "Grant"]], "Select Type", required: true) ?>
            </div>
            <div class="mt-6">
                <?= button("submit", "Add Scholarship", "submit", "add_scholarship", "purple") ?>
            </div>
        </form>
    </div>
    
    <div class="mb-6">
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Name") ?>
                <?= th("Type") ?>
                <?= th("Amount") ?>
                <?= th("Recipients") ?>
                <?= th("Status") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading scholarships...", 6) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
