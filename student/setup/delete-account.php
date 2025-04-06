<?php
require_once relative_path("includes/components.php");

$title = 'Cancel Registration'; // Set the page title
$disabled = user()['approved'];

// Start output buffering to capture the content
ob_start();
?>
<div class="border py-8 space-y-4">
    <p class="text-center dark:text-white">
        <?php if($disabled): ?>
            Registration process has been completed. You can no longer delete your account
        <?php else: ?>
            Are you sure you want to halt your registration? Note that all provided information will be deleted
        <?php endif; ?>
    </p>
    <?php if(!$disabled): ?>
    <form action="<?= url("student/submit.php") ?>" method="POST">
        <div class="grid grid-cols-1 items-center m-auto max-w-48">
            <?= input("hidden", name: "user_id", value: user()["id"]) ?>
            <?= button("submit", "Cancel My Registration", "submit", "delete-account", "red")  ?>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
