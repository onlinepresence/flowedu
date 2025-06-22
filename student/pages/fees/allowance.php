<?php
require_once relative_path("includes/components.php");

$title = 'My Allowance'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Bank Details Section -->
    <div class="mb-4">
        <h2 class="my-6 text-xl font-semibold text-gray-700 dark:text-gray-200">Bank Account Details</h2>
        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">
                        <span class="text-gray-700 dark:text-gray-400">Bank Name</span>
                        <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                            value="<?php echo user()['account_bank']; ?>" readonly>
                    </label>
                </div>
                <div>
                    <label class="block text-sm">
                        <span class="text-gray-700 dark:text-gray-400">Account Number</span>
                        <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input"
                            value="<?php echo user()['account_number']; ?>" readonly>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Allowance History Section -->
    <h2 class="mb-6 text-xl font-semibold text-gray-700 dark:text-gray-200">Allowance History</h2>
    <?php
    // Dummy data for allowance history
    $allowances = [
        [
            'month' => 'January 2024',
            'amount' => '50000',
            'payment_date' => '2024-01-15',
            'status' => 'Paid',
            'reference' => 'ALW-2024-001'
        ],
        [
            'month' => 'December 2023',
            'amount' => '50000',
            'payment_date' => '2023-12-15',
            'status' => 'Paid',
            'reference' => 'ALW-2023-012'
        ]
    ];

    if (empty($allowances)) {
        echo table_start();
        echo tr_start();
        echo th('Month');
        echo th('Amount (₦)');
        echo th('Payment Date');
        echo th('Status');
        echo th('Reference');
        echo tr_end();
        
        echo tbody_start();
        foreach ($allowances as $allowance) {
            echo tr_start();
            echo td($allowance['month']);
            echo td(number_format($allowance['amount'], 2));
            echo td(date('d M, Y', strtotime($allowance['payment_date'])));
            echo td('<span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">' . $allowance['status'] . '</span>');
            echo td($allowance['reference']);
            echo tr_end();
        }
        echo tbody_end();
        echo table_end();
    } else {
        echo placeholder_element('No allowance history found', 'We haven\'t recorded any allowance payments yet. Your allowance payments will appear here once processed.');
    }
    ?>
</div>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Add any JavaScript functionality here if needed
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
