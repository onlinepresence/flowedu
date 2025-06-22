<?php
require_once relative_path("includes/components.php");

$title = 'Payment History'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<?= placeholder_element("No payment history found.", "You have not made any payments yet. Please make a payment to view your payment history.", "fas fa-money-check-alt") ?>
<?php if(1 == 2): ?>
    <?= table_start() ?>
        <?= thead_start() ?>
            <?= tr_start() ?>
                <?= th("Amount") ?>
                <?= th("Payment Method") ?>
                <?= th("Reason") ?>
                <?= th("Payment Date") ?>
            <?= tr_end() ?>
        <?= thead_end() ?>
        <?= tbody_start() ?>
            <?= tr_start() ?>
                <?= td("GHC 100.00") ?>
                <?= td("M-PESA") ?>
                <?= td("Payment for registration") ?>
                <?= td("12th March 2024") ?>
            <?= tr_end() ?>
        <?= tbody_end() ?>
    <?= table_end() ?>
<?php endif; ?>
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
