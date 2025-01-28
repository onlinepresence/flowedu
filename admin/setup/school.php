<?php
require_once relative_path("includes/components.php");

$title = 'Setup Schools'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<form action="save_school.php" method="POST" enctype="multipart/form-data">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- School Name -->
        <?= input('text', 'School Name', 'name', '', true, placeholder("Name of School")) ?>

        <!-- School Address -->
        <?= input('text', 'Address', 'address', '', true, placeholder("School Address")) ?>

        <!-- Email -->
        <?= input('email', 'Email Address', 'email', attributes: placeholder("School Email")) ?>

        <!-- Phone -->
        <?= input('tel', 'Phone Number', 'phone', attributes: placeholder("School Phone Number")) ?>

        <!-- Website -->
        <?= input('url', 'Website', 'website', attributes: placeholder("School Official website")) ?>

        <!-- School Logo -->
        <?= input('file', 'School Logo', 'logo') ?>

        <!-- Description -->
        <?= textarea('description', 'Description', '', '',placeholder('Provide a brief description of the school')) ?>
    </div>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48 w-auto">
        <?= button('submit', 'Save School', 'submit', 'setup_school') ?>
    </div>
</form>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
