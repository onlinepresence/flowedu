<?php
require_once relative_path("includes/components.php");

$title = 'Parent/Guardian Information'; // Set the page title
$guardian = guardian();

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("student/submit.php") ?>" method="POST" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <!-- Hidden User ID -->
    <?php echo input("hidden", "", "student_id", user()['student_id']); ?>

    <!-- item id -->
    <?php echo input("hidden", "", "id", $guardian['id'] ?? 0); ?>

    <?php $is_student = true; require_once relative_path("/student/setup/guardian-form.php") ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", empty($guardian) ? "Create Guardian" : "Save Changes", "submit", "save_guardian", "blue") ?>
    </div>
</form>
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
