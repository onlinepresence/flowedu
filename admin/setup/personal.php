<?php
require_once relative_path("includes/components.php");

$title = 'Personal Information'; // Set the page title
$user = user();

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
                $user["username"] ?? '',
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
                $user["lastname"] ?? "",
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
                $user["othernames"] ?? '',
                true,
                ["placeholder" => "Enter your other names"]
            ); ?>
        </div>

        <!-- Admin Type -->
        <div>
            <?php echo select(
                "",
                "Admin Type",
                [
                    "admin" => "Super Admin",
                    "hod" => "Head of Department",
                    "dean" => "Faculty Dean" // Add more options dynamically as needed
                ],
                value: $_SESSION["user_type"],
                attributes:["placeholder" => "Select admin type", "disabled" => "disabled"]
            ); ?>
            <?= input("hidden",name:"type", value: $_SESSION["admin_register"] ? 1 : ($user["type"] ?? 2)) ?>
        </div>

        <div>
            <?php echo input_h(
                "text",
                "Ghana Card Number",
                "ghana_card",
                $user["ghana_card"] ?? '',
                true,
                "Include all dashes",
                array_merge(placeholder("GHA-XXXXXXXXX-X"), attribute("minlength", 6), attribute("required"))
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
