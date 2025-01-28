<?php
require_once relative_path("includes/components.php");

$title = 'Personal Information'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<!-- Personal Information Form -->
<form action="<?= url("admin/submit.php") ?>" method="POST" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Hidden User ID -->
        <?php echo input("hidden", "", "user_id", $_SESSION['user_id'], true); ?>

        <!-- Username -->
        <div>
            <?php echo input(
                "text",
                "Username",
                "username",
                "",
                true,
                ["placeholder" => "Enter your username"]
            ); ?>
        </div>

        <!-- Last Name -->
        <div>
            <?php echo input(
                "text",
                "Last Name",
                "lastname",
                "",
                true,
                ["placeholder" => "Enter your last name"]
            ); ?>
        </div>

        <!-- Other Names -->
        <div>
            <?php echo input(
                "text",
                "Other Names",
                "othernames",
                "",
                true,
                ["placeholder" => "Enter your other names"]
            ); ?>
        </div>

        <!-- Admin Type -->
        <div>
            <?php echo select(
                "type",
                "Admin Type",
                [
                    "admin" => "Super Admin",
                    "hod" => "Head of Department",
                    "dean" => "Dean" // Add more options dynamically as needed
                ],
                value: $_SESSION["user_type"],
                attributes:["placeholder" => "Select admin type", "disabled" => "disabled"]
            ); ?>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Setup Admin Account", "submit", "create_admin", "blue") ?>
    </div>
</form>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
