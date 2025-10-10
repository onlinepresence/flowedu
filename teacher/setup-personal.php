<?php
require_once relative_path("includes/components.php");

$title = 'Setup Account'; // Set the page title
$teacher = user();

// Start output buffering to capture the content
ob_start();
?>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md space-y-6">
        <?php 
            $hasUsername = !empty($teacher["username"]);
            $requiresReset = !empty($teacher["password_reset_required"]);
            $isProfilePage = ($hasUsername && !$requiresReset);
        ?>

        <?php if($isProfilePage): ?>
            <div class="flex gap-4 border-b pb-2 mb-4 text-sm font-medium text-gray-700 dark:text-gray-200">
                <a href="#" class="menu-link text-blue-600 dark:text-blue-300" data-view="details">Change Details</a>
                <a href="#" class="menu-link" data-view="password">Change Password</a>
            </div>
        <?php endif; ?>

        <div id="view-container">
            <?php if($isProfilePage || (!$hasUsername && $requiresReset)): ?>
                <!-- reset password -->
                <form action="<?= url('teacher/submit.php') ?>" method="POST" 
                    id="view-password" class="<?= $isProfilePage ? "hidden" : "" ?>">
                    <!-- Hidden User ID -->
                    <?= hidden_input("user_id", $teacher['user_id']); ?>
                    
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend(!$isProfilePage ? "Reset Password" : "Set Your Password"); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
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

            <?php if($isProfilePage || (!$hasUsername && !$requiresReset)): ?>
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                            <!-- Profile Picture -->
                            <div>
                                <?php
                                    $sub_text = $teacher["profile_pic"] 
                                        ? "<a href=\"".asset($teacher['profile_pic'], false)."\" target=\"_blank\">View Profile Picture</a>" 
                                        : "";
                                    echo input_h("file", "Profile Picture", "profile_pic", required: empty($teacher["profile_pic"]), 
                                        sub_text: $sub_text, attributes: attribute("accept", "image/*"));
                                ?>
                            </div>

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

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

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
                            <?= select("rank", "Rank", [
                                "Assistant Lecturer" => "Assistant Lecturer",
                                "Lecturer" => "Lecturer",
                                "Senior Lecturer" => "Senior Lecturer",
                                "Associate Professor" => "Associate Professor",
                                "Professor" => "Professor"
                            ], true, value: $teacher["rank"] ?? ""); ?>

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
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- DOCUMENTS -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Academic Documents"); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
    </div>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        $('.menu-link').on('click', function(e){
            e.preventDefault();
            $('.menu-link').removeClass('text-blue-600 dark:text-blue-300');
            $(this).addClass('text-blue-600 dark:text-blue-300');

            const view = $(this).data('view');
            $('#view-container > form').addClass('hidden');
            $('#view-' + view).removeClass('hidden');
        });
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
