<?php
require_once relative_path("includes/components.php");

$title = 'Page not Found'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<div class="text-center text-sm m-auto w-fit mt-16">
  <i class="fas fa-sad-tear text-gray-500 text-6xl mb-4 mt-8"></i>
  <h1 class="text-6xl font-semibold text-gray-700 dark:text-gray-200">
    404
  </h1>
  <p class="text-gray-700 dark:text-gray-300">
    Page not found. Check the address or
    <a
      class="text-purple-600 hover:underline dark:text-purple-300"
      href="<?= $_SERVER["HTTP_REFERER"] ?? "/" ?>"
    >
      <?= !empty($_SERVER["HTTP_REFERER"]) ? "go back" : "go to home" ?>
    </a>
    .
  </p>
</div>
  
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/'.(isset($_SESSION['user_id']) ? 'auth' : 'guest').'.php');
