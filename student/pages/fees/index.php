<?php
require_once relative_path("includes/components.php");

$title = 'Fee Details'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<div class="container px-6 mx-auto grid">

    <!-- Fee Summary Card -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 mb-4">
        <h4 class="mb-4 font-semibold text-gray-600 dark:text-gray-300">
            Current Term Fees Summary
        </h4>
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        Total Fee
                    </p>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        GHC 0.00
                    </p>
                </div>
            </div>
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        Paid Amount
                    </p>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        GHC 0.00
                    </p>
                </div>
            </div>
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        Balance
                    </p>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        GHC 0.00
                    </p>
                </div>
            </div>
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                <div>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        Due Date
                    </p>
                    <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        N/A
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Breakdown Table -->
    <?php if(true):
        echo placeholder_element("No fee breakdown found.", "You have not made any payments yet. Please make a payment to view your fee breakdown.", "fas fa-money-check-alt");
    else: ?>
    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <?php
            echo table_start(['class' => 'whitespace-no-wrap']);
            ?>
                <?= thead_start() ?>
                    <?php echo tr_start(); ?>
                        <?php
                        echo th('Fee Item');
                        echo th('Amount');
                        echo th('Status');
                        echo th('Due Date');
                        echo tr_end();
                        ?>
                <?= thead_end() ?>
                <?php echo tbody_start(); ?>
                    <?php echo tr_start(); ?>
                        <?php
                        echo td('Tuition Fee');
                        echo td('KES 30,000');
                        echo td('<span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full">Partial</span>');
                        echo td('30th April 2024');
                        echo tr_end();
                        ?>
                    <?php echo tr_start(); ?>
                        <?php
                        echo td('Development Fee');
                        echo td('KES 10,000');
                        echo td('<span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full">Paid</span>');
                        echo td('30th April 2024');
                        echo tr_end();
                        ?>
                    <?php echo tr_start(); ?>
                        <?php
                        echo td('Activity Fee');
                        echo td('KES 5,000');
                        echo td('<span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full">Unpaid</span>');
                        echo td('30th April 2024');
                        echo tr_end();
                        ?>
                <?php echo tbody_end(); ?>
            <?php echo table_end(); ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
