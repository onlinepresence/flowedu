<?php
require_once relative_path("includes/components.php");

$title = 'My Results'; // Set the page title
$user = user();

// External results URL
$results_url = "https://share.google/va8BHKN9hp4BHxKAq";

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <?= information_bar(
        "Your results are available through an external system. Click the button below to access your results.",
        "info",
        false,
        attribute("class", "mb-6")
    ) ?>

    <div class="flex flex-col items-center justify-center p-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="text-center mb-6">
            <i class="fas fa-external-link-alt text-6xl text-purple-600 dark:text-purple-400 mb-4"></i>
            <h3 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                Access Your Results
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Your academic results are hosted on an external platform. Click the button below to view them.
            </p>
        </div>

        <a 
            href="<?= htmlspecialchars($results_url) ?>" 
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center px-6 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"
        >
            <i class="fas fa-external-link-alt mr-2"></i>
            View My Results
        </a>

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-info-circle mr-1"></i>
            This link will open in a new tab
        </p>
    </div>

    <!-- Alternative: If you want to embed the results -->
    <!-- 
    <div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <iframe 
            src="<?= htmlspecialchars($results_url) ?>" 
            class="w-full h-screen border-0 rounded-lg"
            title="Student Results"
        ></iframe>
    </div>
    -->
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
