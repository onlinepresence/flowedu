<?php
require_once relative_path("includes/components.php");
$title = 'Backup & Restore';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-download mr-2"></i>Backup Database
            </h3>
            <form action="<?= url('admin/submit.php') ?>" method="POST">
                <?= input("hidden", "", "request_type", "backup_database") ?>
                <?= information_bar("Create a backup of the entire database. Recommended before major updates.", "info", false, attribute("class", "mb-4")) ?>
                <div class="mt-6">
                    <?= button("submit", "Create Backup", "submit", "backup_database", "purple") ?>
                </div>
            </form>
        </div>
        
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-upload mr-2"></i>Restore Database
            </h3>
            <form action="<?= url('admin/submit.php') ?>" method="POST" enctype="multipart/form-data">
                <?= input("hidden", "", "request_type", "restore_database") ?>
                <?= information_bar("Warning: Restoring will replace all current data. Ensure you have a current backup.", "warning", false, attribute("class", "mb-4")) ?>
                <?= input_h("file", "Backup File", "backup_file", required: true, attributes: array_merge(attribute("accept", ".sql"), data_attr("file-upload", "backup"))) ?>
                <div class="mt-6">
                    <?= button("submit", "Restore Database", "submit", "restore_database", "red") ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-history mr-2"></i>Backup History
        </h3>
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Backup Date") ?>
                <?= th("File Name") ?>
                <?= th("Size") ?>
                <?= th("Created By") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("No backups available.", 5) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
