<?php
require_once relative_path("includes/components.php");

$title = 'Admission Status'; // Set the page title
$guardian = guardian();
$disabled = !$guardian || !user()['approved'];

if(!$guardian && !isset($_SESSION["errors"]["system_message"])){
    $_SESSION["errors"]["system_message"] = "Guardian information not provided";
}

// Start output buffering to capture the content
ob_start();
?>
<div class="border py-8 space-y-4">
    <p class="text-center dark:text-white">
        <?php if(is_null(user()['approved'])): ?>
            Admission is in edit mode. Please provide your personal information to proceed
        <?php elseif(!$guardian): ?>
            Provide parent/guardian details to complete your request
        <?php elseif(!user()['approved']) : ?>
            Admission details have been submitted, awaiting approval from admins
        <?php else: ?>
            Congratulations <?= user()['lastname'] ?>, your account has been approved
        <?php endif; ?>
    </p>
    <?php if(!is_null(user()['approved'])): ?>
    <form action="<?= url("student/submit.php") ?>" method="POST">
        <input type="hidden" name="is_new" value="<?= intval(!user()['approved']) ?>">
        <div class="grid grid-cols-1 items-center m-auto max-w-48">
            <?= button("submit", $disabled ? "Awaiting Approval" : "Go to Dashboard", "submit", "change_status", $disabled ? "zinc" : "blue", $disabled ? array_merge(
                attribute("disabled"),
                attribute("class", "border cursor-disabled")
                ) : [])  ?>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
