<?php
require_once relative_path("includes/components.php");

$teacher = user();

$hasUsername = !empty($teacher["username"]);
$requiresReset = $teacher["password_reset_required"];
$isProfilePage = $teacher["is_onboarded"];

$title = $isProfilePage ? 'Update Profile' : 'Setup Account'; // Set the page title

// Get department name if available
$department_name = "";
if(!empty($teacher["department_id"])) {
    $dept = fetchData("name", "departments", "id = " . $teacher["department_id"]);
    $department_name = $dept ? $dept["name"] : "";
}

// Start output buffering to capture the content
ob_start();
?>
<?php if($isProfilePage): ?>
    <!-- Profile Mode: Two-column layout similar to student profile -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left Column: Profile Picture and Quick Info -->
        <div class="flex flex-col col-span-1 gap-6 lg:gap-8">
            <!-- Profile Picture Section -->
            <div class="relative p-6 bg-white rounded-lg shadow-md h-max dark:bg-gray-800">
                <div class="relative w-32 h-32 m-auto overflow-hidden rounded-full">
                    <img id="profile-pic" src="<?= asset($teacher['profile_pic'] ?? 'default-avatar.png') ?>" class="object-cover w-full h-full cursor-pointer" alt="Profile Picture" onclick="$('#file-input').click()">
                    <input type="file" id="file-input" class="hidden" accept="image/*">
                </div>
                <div class="absolute flex-col items-center hidden gap-1 text-center top-6 right-10 lg:right-14" id="save-button-container">
                    <button id="save-button" class="px-2 py-1 text-white bg-blue-500 rounded hover:bg-blue-600" title="Save">
                        <i class="fas fa-save"></i>
                    </button>
                    <button id="cancel-edit" type="button" class="px-2 py-1 text-white bg-red-500 rounded hover:bg-red-600" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Other user information -->
                <div class="mt-6 text-center">
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">
                        <?= ($teacher['lastname'] ?? '') . ' ' . ($teacher['othernames'] ?? '') ?>
                    </h3>
                    
                    <div class="text-gray-600 dark:text-gray-300">
                        <p class="">
                            <span class="font-medium">
                                <?= $department_name ?: 'Department not assigned' ?>
                            </span>
                        </p>
                        <?php if(!empty($teacher['staff_id'])): ?>
                        <p class="mt-2">
                            <span class="font-medium">
                                <i class="mr-2 fas fa-id-badge"></i> <?= $teacher['staff_id'] ?>
                            </span>
                        </p>
                        <?php endif; ?>
                        <?php if(!empty($teacher['rank'])): ?>
                        <p class="mt-2">
                            <span class="font-medium">
                                <i class="mr-2 fas fa-award"></i> <?= ucfirst($teacher['rank']) . " | " . $teacher['qualification'] ?>
                            </span>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Change Password Section (Profile Mode Only) -->
            <?php if($isProfilePage): ?>
            <div class="p-6 bg-white rounded-lg shadow-md h-max dark:bg-gray-800">
                <?= h3("Change Password") ?>
                <form action="<?= url('teacher/submit.php') ?>" method="POST" id="change-password-form">
                    <!-- Hidden User ID -->
                    <?= hidden_input("user_id", $teacher['user_id']); ?>
                    
                    <div class="grid grid-cols-1 gap-4 mt-4">
                        <?= input("password", "New Password", "password", "", true, placeholder("Enter new password")); ?>
                        <?= input("password", "Confirm Password", "confirm_password", "", true, placeholder("Confirm new password")); ?>

                        <!-- Password helper text -->
                        <div>
                            <?= password_hint_component() ?>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?= button("submit", "Change Password", "submit", "set_password", "blue", attribute("class", "w-full")) ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Profile Form -->
        <div class="col-span-1 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 lg:col-span-2">
<?php else: ?>
    <!-- Setup Mode: Single column layout (existing design) -->
    <div class="p-6 space-y-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
<?php endif; ?>

        <div id="view-container">
            <?php if(!$isProfilePage && $requiresReset): ?>
                <!-- reset password (Setup Mode Only) -->
                <form action="<?= url('teacher/submit.php') ?>" method="POST" 
                    id="view-password">
                    <!-- Hidden User ID -->
                    <?= hidden_input("user_id", $teacher['user_id']); ?>
                    
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend(!$isProfilePage ? "Reset Password" : "Set Your Password"); ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?= input("password", "New Password", "password", "", true, placeholder("Enter new password")); ?>
                            <?= input("password", "Confirm Password", "confirm_password", "", true, placeholder("Confirm new password")); ?>
                            <?php if(!$isProfilePage): ?>
                                <?= hidden_input("new_user", "1") ?>
                            <?php endif; ?>

                            <!-- Password helper text -->
                            <div class="sm:col-span-2 lg:col-span-3">
                                <?= password_hint_component() ?>
                            </div>
                        </div>

                        <div class="mt-4 sm:w-48">
                            <?= button("submit", $isProfilePage ? "Change Password" : "Set Password", "submit", "set_password", "blue"); ?>
                        </div>
                    <?= fieldset_end(); ?>
                </form>
            <?php endif; ?>

            <?php if($isProfilePage || !$requiresReset): ?>
                <!-- change details -->
                <form action="<?= url('teacher/submit.php') ?>" method="POST" enctype="multipart/form-data" 
                    id="view-details" class="space-y-6">
                    <!-- User ID -->
                    <?= hidden_input("user_id", $teacher['user_id']); ?>

                    <!-- PERSONAL INFORMATION -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Personal Information"); ?>

                        <!-- <?= information_bar(
                            "Please ensure your profile picture has a solid background (preferably blue or red), minimum size 300 x 400 pixels (7:9 ratio).",
                            attributes: attribute("class", "mb-4 text-sm rounded-sm")
                        ); ?> -->

                        <?php if(!$isProfilePage): ?>
                            <?= information_bar(
                                "All fields with * mean they are required fields",  attributes: attribute("class", "mb-4 text-sm rounded-sm text-center")
                            ) ?>

                        <?php endif; ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?php if(!$isProfilePage): ?>
                            <!-- Profile Picture (only shown in setup mode, profile mode has it in left sidebar) -->
                            <div>
                                <?php
                                    $sub_text = $teacher["profile_pic"] 
                                        ? "<a href=\"".asset($teacher['profile_pic'], false)."\" target=\"_blank\">View Profile Picture</a>" 
                                        : "";
                                    echo input_h("file", "Profile Picture", "profile_pic", required: empty($teacher["profile_pic"]), 
                                        sub_text: $sub_text, attributes: attribute("accept", "image/*"));
                                ?>
                            </div>
                            <?php endif; ?>

                            <!-- Last Name -->
                            <div>
                                <?= input("text", "Last Name", "lastname", $teacher["lastname"] ?? "", true, placeholder("Enter your last name")); ?>
                            </div>

                            <!-- Other Names -->
                            <div>
                                <?= input("text", "Other Names", "othernames", $teacher["othernames"] ?? "", true, placeholder("Enter your other names")); ?>
                            </div>

                            <!-- Gender -->
                            <?= select("gender", "Gender", ["male" => "Male", "female" => "Female"], true, value: $teacher["gender"] ?? "", required: true); ?>

                            <!-- Date of Birth -->
                            <?= input("date", "Date of Birth", "date_of_birth", $teacher["date_of_birth"] ?? "", true); ?>

                            <!-- Nationality -->
                            <?= select("nationality", "Nationality", nationalities(), true, value: $teacher["nationality"] ?? "ghanaian"); ?>

                            <!-- Ghana Card -->
                            <?= input("text", "Ghana Card Number", "ghana_card", $teacher["ghana_card"] ?? "", true, placeholder("GHA-XXXXXXXXX-X")); ?>

                            <!-- Contact Address -->
                            <?= input("text", "Contact Address", "contact_address", $teacher["contact_address"] ?? "", true, placeholder("House No. / GPS Address / Street, City, Region")); ?>

                            <!-- Phone -->
                            <?= input("tel", "Phone Number", "phone_number", $teacher["phone_number"] ?? "", true, placeholder("e.g., 0241234567")); ?>

                        </div>
                    <?= fieldset_end(); ?>

                    <!-- PROFESSIONAL DETAILS -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Professional Details"); ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                            <!-- Staff ID -->
                            <?= input_h("text", "Staff ID", "staff_id", $teacher["staff_id"] ?? "", true, "Your staff ID will also serve as your username", array_merge(
                                placeholder("Enter staff ID"),
                                attribute("readonly", $teacher["staff_id"] ? null : false)
                            )); ?>

                            <!-- Department -->
                            <?php 
                                $departments = departments(); 
                                $dept_opts = [];
                                foreach($departments as $dept){
                                    $dept_opts[$dept["id"]] = $dept["name"];
                                }
                                echo select("department_id", "Department", $dept_opts, true, value: $teacher["department_id"] ?? "");
                            ?>

                            <!-- Rank -->
                            <?= select("rank", "Rank", teacher_ranks(), true, value: $teacher["rank"] ?? ""); ?>

                            <!-- Highest Qualification -->
                            <?= select("qualification", "Highest Qualification", [
                                "PhD" => "PhD",
                                "MPhil" => "MPhil",
                                "MSc" => "MSc",
                                "B.Ed" => "B.Ed",
                                "BSc" => "BSc",
                                "Other" => "Other"
                            ], true, value: $teacher["qualification"] ?? ""); ?>

                            <!-- Field of Specialization -->
                            <?= input("text", "Field of Specialization", "specialization", $teacher["specialization"] ?? "", true, placeholder("e.g., Mathematics, Computer Science, English Language")); ?>

                            <!-- Employment Type -->
                            <?= select("employment_type", "Employment Type", [
                                "Full-time" => "Full-time",
                                "Part-time" => "Part-time",
                                "Visiting" => "Visiting"
                            ], true, value: $teacher["employment_type"] ?? "Full-time"); ?>

                            <!-- Years of Experience -->
                            <?= input("number", "Years of Experience", "years_experience", $teacher["years_experience"] ?? "", true, attribute("min", 0)); ?>

                            <!-- date of appointment -->
                            <?= input("date", "Date of Appointment", "date_of_appointment", $teacher["date_of_appointment"] ?? "", true); ?>
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- DOCUMENTS -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Academic Documents"); ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?php 
                                $sub_text = $teacher["cv"] 
                                    ? "<a href=\"".asset($teacher['cv'], false)."\" target=\"_blank\">View Document</a>" 
                                    : "";
                                echo input_h("file", "Curriculum Vitae (CV)", "cv", sub_text: $sub_text, required: empty($teacher["cv"]), attributes: attribute("accept", ".pdf,.doc,.docx")); ?>

                            <?php 
                                $sub_text = $teacher["certificate"] 
                                    ? "<a href=\"".asset($teacher['certificate'], false)."\" target=\"_blank\">View Document</a>" 
                                    : "";
                                echo input_h("file", "Highest Certificate", "certificate", sub_text: $sub_text, required: empty($teacher["certificate"]), attributes: attribute("accept", ".pdf,.jpg,.png")); ?>

                            <?php 
                                $sub_text = $teacher["id_document"] 
                                    ? "<a href=\"".asset($teacher['id_document'], false)."\" target=\"_blank\">View Document</a>" 
                                    : "";
                                echo input_h("file", "National ID", "id_document", sub_text: $sub_text, required: empty($teacher["id_document"]), attributes: attribute("accept", ".pdf,.jpg,.png")); ?>
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- OPTIONAL / ADDITIONAL -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Additional Information"); ?>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?= input("text", "Emergency Contact Name", "emergency_name", $teacher["emergency_name"] ?? "", false, placeholder("Name of person to contact in emergency")); ?>

                            <?= input("tel", "Emergency Contact Number", "emergency_phone", $teacher["emergency_phone"] ?? "", false, placeholder("024xxxxxxx")); ?>

                            <?= textarea("research_interests", "Research Interests / Short Bio", $teacher["research_interests"] ?? "", attributes: placeholder("e.g., Artificial Intelligence, Educational Psychology, etc.")); ?>
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- SUBMIT BUTTON -->
                    <div class="mt-4 sm:w-48">
                        <?= button(
                            "submit",
                            empty($teacher["username"]) ? "Submit Lecturer Details" : "Save Changes",
                            "submit",
                            empty($teacher["username"]) ? "save_teacher" : "update_teacher",
                            "blue",
                        ); ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
<?php if($isProfilePage): ?>
        </div>
    </div>
<?php else: ?>
    </div>
<?php endif; ?>

<?php 
$profilePicScript = "";
if($isProfilePage) {
    $user_id = $teacher['user_id'];
    $submit_url = url("teacher/submit.php");
    $profilePicScript = <<<JS
        // Profile picture change functionality (similar to student profile)
        var original_path = $("#profile-pic").attr("src");

        $('#file-input').on('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#profile-pic').attr('src', e.target.result);
                    $('#save-button-container').removeClass("hidden").addClass("flex");
                };
                reader.readAsDataURL(file);
            }
        });

        $('#save-button').on('click', function () {
            const formData = new FormData();
            const fileInput = $('#file-input')[0];
            if (fileInput.files[0]) {
                formData.append('profile_pic', fileInput.files[0]);
                formData.append("submit", "change_picture");
                formData.append("user_id", "$user_id");

                ajaxCall({
                    url: "$submit_url",
                    method: 'POST',
                    sendRaw: true,
                    data: formData
                }).then(response => {
                    if(response.status){
                        original_path = $("#profile-pic").attr('src');
                        $("#cancel-edit").click();
                        alert_box("Profile picture updated successfully", "success");
                    }else{
                        alert_box(response.message || "Failed to update profile picture", "error");
                    }
                })
            }
        });

        $("#cancel-edit").click(function () {
            $("#profile-pic").attr("src", original_path);
            $('#save-button-container').addClass("hidden").removeClass("flex");
            $('#file-input').val(''); // Clear the file input
        });
JS;
}

$menuLinkScript = "";
if(!$isProfilePage && $requiresReset) {
    // Only need tab navigation in setup mode when password reset is required
    $menuLinkScript = <<<JS
        $('.menu-link').on('click', function(e){
            e.preventDefault();
            $('.menu-link').removeClass('text-blue-600 dark:text-blue-300');
            $(this).addClass('text-blue-600 dark:text-blue-300');

            const view = $(this).data('view');
            $('#view-container > form').addClass('hidden');
            $('#view-' + view).removeClass('hidden');
        });
JS;
}

$scripts = <<<HTML
<script>
    $(document).ready(function(){
        $menuLinkScript
        
        $profilePicScript
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
