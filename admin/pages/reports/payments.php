<?php
require_once relative_path("includes/components.php");
$title = 'Payment Reports';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-chart-bar mr-2"></i>Payment Reports
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST">
            <?= input("hidden", "", "request_type", "generate_payment_report") ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?= input("date", "From Date", "from_date", "", true) ?>
                <?= input("date", "To Date", "to_date", "", true) ?>
                <?php $programs = programs(); ?>
                <?= select("program_id", "Program", $programs, "All Programs", keys: select_keys("id", "name")) ?>
            </div>
            <div class="mt-6">
                <?= button("submit", "Generate Report", "submit", "generate_payment_report", "purple") ?>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
