<?php
require_once relative_path("includes/components.php");

$title = ''; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
  <svg
    class="w-12 h-12 mt-8 text-purple-200"
    fill="currentColor"
    viewBox="0 0 20 20"
  >
    <path
      fill-rule="evenodd"
      d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
      clip-rule="evenodd"
    ></path>
  </svg>
  <h1 class="text-6xl font-semibold text-gray-700 dark:text-gray-200">
    404
  </h1>
  <p class="text-gray-700 dark:text-gray-300">
    Page not found. Check the address or
    <a
      class="text-purple-600 hover:underline dark:text-purple-300"
      href="<?= url($_SERVER["HTTP_REFERER"]) ?>"
    >
      go back
    </a>
    .
  </p>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/'.(isset($_SESSION['user_id']) ? 'auth' : 'guest').'.php');
