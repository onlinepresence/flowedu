<?php
require_once relative_path("includes/components.php");

$title = 'Setup Schools'; // Set the page title
$school = school();

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("admin/submit.php") ?>" method="POST" enctype="multipart/form-data">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <input type="hidden" name="school_id" value="<?= $school['id'] ?? 0 ?>">

        <!-- School Name -->
        <?= input('text', 'School Name', 'name', $school['name'] ?? '', true, placeholder("Name of School")) ?>

        <!-- School Address -->
        <?= input('text', 'Address', 'address', $school['address'] ?? '', true, placeholder("123 Kwame Nkrumah Ave, Accra, Ghana")) ?>

        <!-- Email -->
        <?= input('email', 'Email Address', 'email', value: $school['email'] ?? '', attributes: placeholder("School Email")) ?>

        <!-- Phone -->
        <?= input('tel', 'Phone Number', 'phone', value: $school['phone'] ?? '', attributes: placeholder("School Phone Number")) ?>

        <!-- Website -->
        <?= input('url', 'Website', 'website', value: $school['website'] ?? '', attributes: placeholder("School Official website")) ?>

        <!-- School Logo -->
        <?php if(isset($school['logo']))
            $sub_text = "<a href='".url($school['logo'])."' target='_blank' class='text-blue-600 hover:text-'>School Logo</a>" 
        ?>
        <?= input_h('file', 'School Logo', 'logo', sub_text: $sub_text ?? '') ?>

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
