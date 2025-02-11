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

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- guardian name -->
        <div>
            <?= input(label: "Name", name: "name", value: $guardian["name"] ?? '', required: true, attributes: placeholder("Guardian Name")) ?>
        </div>

        <!-- guardian relationship -->
         <div>
            <?= select("relationship", "Relationship", ["Father", "Mother", "Guardian"], true, required: true, value: $guardian["relationship"] ?? ''); ?>
         </div>

         <!-- guardian address -->
        <div>
            <?= input(label: "Residence Address", name: "address", value: $guardian["address"] ?? '', attributes: placeholder("H.No 12, Atomic Street, East Legon, Accra, Greater Accra")); ?>
        </div>

        <!-- phone number -->
        <div>
            <?= input(label: "Phone Number", name: "phone_number", value: $guardian["phone_number"] ?? '', required: true, attributes: placeholder("Guardian Phone Number")) ?>
        </div>

        <!-- email -->
        <div>
            <?= input('email', "Email", "email", $guardian["email"] ?? '', attributes: placeholder("Guardian Email Address")) ?>
        </div>
    </div>

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
