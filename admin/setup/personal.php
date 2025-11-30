<?php
require_once relative_path("includes/components.php");

$title = 'Personal Information'; // Set the page title
$user = user();
$school_is_ready = school()["ready"];

// Start output buffering to capture the content
ob_start();
?>
<!-- Personal Information Form -->
<form action="<?= url("admin/submit.php") ?>" method="POST" <?= $school_is_ready ? 'enctype="multipart/form-data"' : "" ?> class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <?php if(session("admin_register") == 1 &&  empty($user["username"])): ?>
        <?= information_bar("Complete this form to finish your Super Admin registration.", can_hide: true, attributes: attribute("class", "mb-4 text-sm")) ?>
    <?php endif; ?>
    
        <!-- Hidden User ID -->
        <?php echo input("hidden", "", "user_id", session('user_id'), true); ?>

        <?php if(!$school_is_ready): ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Username -->
            <div>
                <?php echo input(
                    "text",
                    "Username",
                    "username",
                    $user["username"] ?? '',
                    true,
                    placeholder("Enter your username")
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
                <?= input("hidden",name:"type", value: isset($_SESSION["admin_register"]) ? 1 : ($user["type"] ?? 2)) ?>
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
    <?php else: ?>
        <div class="space-y-4">
            <!-- PERSONAL INFORMATION -->
            <?= fieldset_start(attribute("class")); ?>
                    <?= fieldset_legend("Personal Information"); ?>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <!-- Profile Photo -->
                        <div>
                            <?php
                                $sub_text = $user["profile_pic"]
                                    ? "<a href=\"".asset($user['profile_pic'], false)."\" target=\"_blank\">View Photo</a>"
                                    : "";
                                echo input_h("file", "Profile Photo", "profile_pic",
                                    required: empty($user["profile_pic"]),
                                    sub_text: $sub_text,
                                    attributes: attribute("accept", "image/*")
                                );
                            ?>
                        </div>

                        <!-- Last Name -->
                        <?= input("text", "Last Name", "lastname", $user["lastname"] ?? "", true, placeholder("Enter your last name")); ?>

                        <!-- Other Names -->
                        <?= input("text", "Other Names", "othernames", $user["othernames"] ?? "", true, placeholder("Enter your other names")); ?>
                        
                        <!-- Username -->
                        <?php echo input("text","Username","username",$user["username"] ?? '',true,array_merge(placeholder("Enter your username"))); ?>
                        
                        <!-- Gender -->
                        <?= select("gender", "Gender", ["male" => "Male", "female" => "Female"], true, value: $user["gender"] ?? ""); ?>

                        <!-- Phone Number -->
                        <?= input("tel", "Phone Number", "phone_number", $user["phone_number"] ?? "", true, placeholder("e.g., 0241234567")); ?>

                        <!-- Ghana Card -->
                        <?= input("text", "Ghana Card Number", "ghana_card", $user["ghana_card"] ?? "", true, placeholder("GHA-XXXXXXXXX-X")); ?>
                    </div>
                <?= fieldset_end(); ?>

                <!-- PROFESSIONAL DETAILS -->
                <?= fieldset_start(); ?>
                    <?= fieldset_legend("Professional Details"); ?>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <!-- Position Title -->
                        <?= input("text", "Position Title", "position_title", $user["position_title"] ?? "", true, placeholder("e.g., Registrar, Dean, HOD")); ?>
                        
                        <?php if($user["type"] == 3): ?>
                        <!-- Department -->
                        <?php 
                            $departments = departments(columns:["id", "name"]);
                            $departments = $departments ? pluck($departments, "id", "name") : [];
                            echo select("department_id", "Department", $departments, true, value: $user["department_id"] ?? "");
                        ?>
                        
                        <?php elseif($user["type"] == 4): ?>
                        <!-- faculties -->
                        <?php 
                            $faculties = faculties(columns:["id", "name"]);
                            $faculties = $faculties ? pluck($faculties, "id", "name") : [];
                            echo select("faculty_id", "Faculty", $faculties, true, value: $user["faculty_id"] ?? "");
                        ?>
                        <?php endif; ?>

                        <!-- Admin Type -->
                        <?php 
                            $user_types = admin_types(); // if you have an admin_types() helper
                            $user_types = pluck($user_types, "id", "name", true);
                            $user_types[1] = "SUPER ADMIN";     // convert owner to super admin
                            echo select("", "Admin Type", $user_types, true, value: $user["type"] ?? "", attributes: attribute("disabled"));
                            echo hidden_input("type", $user["type"]);
                        ?>

                        <!-- Date of Appointment -->
                        <?= input("date", "Date of Appointment", "date_of_appointment", $user["date_of_appointment"] ?? ""); ?>

                        <!-- Status -->
                        <!-- <?= select("status", "Status", ["active" => "Active", "inactive" => "Inactive", "suspended" => "Suspended"], true, value: $user["status"] ?? "active"); ?> -->
                    </div>
                <?= fieldset_end(); ?>

                <!-- DOCUMENTS -->
                <?= fieldset_start(); ?>
                    <?= fieldset_legend("Supporting Documents"); ?>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <?php 
                            $sub_text = !empty($admin["id_document"])
                                ? "<a href=\"".asset($admin['id_document'], false)."\" target=\"_blank\">View ID</a>"
                                : "";
                            echo input_h("file", "National ID", "id_document", sub_text: $sub_text, attributes: array_merge(
                                attribute("accept", ".pdf,.jpg,.png"),
                                attribute("disabled")
                            ));
                        ?>
                    </div>
                <?= fieldset_end(); ?>
        </div>
    <?php endif; ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", $school_is_ready ? "Update Profile" : "Setup Admin Account", "submit", $school_is_ready ? "update_admin" : "create_admin", "blue") ?>
    </div>
</form>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
