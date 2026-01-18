<?php
require_once relative_path("includes/components.php");
$title = 'User Accounts Management';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-users mr-2"></i>User Accounts
        </h3>
        
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?= input("text", "Search User", "search", "", false, array_merge(attribute("id", "search"), data_attr("filter", "search"))) ?>
            <?php $user_types = [["id" => "", "text" => "All Types"], ["id" => "student", "text" => "Student"], ["id" => "teacher", "text" => "Teacher"], ["id" => "admin", "text" => "Admin"]]; ?>
            <?= select("filter_type", "User Type", $user_types, "All Types", attributes: array_merge(attribute("id", "filter-type"), data_attr("filter", "type"))) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("User") ?>
                <?= th("Email") ?>
                <?= th("Type") ?>
                <?= th("Status") ?>
                <?= th("Last Login") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading users...", 6) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
