<?php
require_once relative_path("includes/components.php");

$title = 'Setup Account'; // Set the page title
$teacher = user();

// Start output buffering to capture the content
ob_start();
?>
    <form action="<?= url('teacher/submit.php') ?>" method="POST" enctype="multipart/form-data" 
        class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md space-y-6">

        <!-- Hidden User ID -->
        <?= hidden_input("user_id", $_SESSION['user_id']); ?>

        <?php 
            $hasUsername = !empty($teacher["username"]);
            $requiresReset = !empty($teacher["password_reset_required"]);
            $isProfilePage = ($hasUsername && !$requiresReset);
        ?>

        <?php if($isProfilePage): ?>
            <div class="flex gap-4 border-b pb-2 mb-4 text-sm font-medium text-gray-700 dark:text-gray-200">
                <a href="#" class="menu-link active" data-view="details">Change Details</a>
                <a href="#" class="menu-link" data-view="password">Change Password</a>
            </div>
        <?php endif; ?>

        <div id="view-container">
            <?php if($isProfilePage || (!$hasUsername && $requiresReset)): ?>
                <!-- reset password -->
                <div id="view-password" class="<?= $isProfilePage ? "hidden" : "" ?>">
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend(!$isProfilePage ? "Reset Password" : "Set Your Password"); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?= input("password", "New Password", "password", "", true, placeholder("Enter new password")); ?>
                            <?= input("password", "Confirm Password", "confirm_password", "", true, placeholder("Confirm new password")); ?>
                            <?php if(!$isProfilePage): ?>
                                <?= hidden_input("new_user", "1") ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4 sm:w-48">
                            <?= button("submit", $isProfilePage ? "Change Password" : "Set Password", "submit", "set_password", "blue"); ?>
                        </div>
                    <?= fieldset_end(); ?>
                </div>
            <?php endif; ?>

            <?php if($isProfilePage || (!$hasUsername && !$requiresReset)): ?>
                <!-- change details -->
                <div id="view-details">
                    <!-- PERSONAL INFORMATION -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Personal Information"); ?>

                        <?= information_bar(
                            "Please ensure your profile picture has a solid background (preferably blue or red), minimum size 300 x 400 pixels (7:9 ratio).",
                            attributes: attribute("class", "mb-4 text-sm rounded-sm")
                        ); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                            <!-- Profile Picture -->
                            <div>
                                <?php
                                    $sub_text = user()["profile_pic"] 
                                        ? "<a href=\"".asset(user()['profile_pic'])."\" target=\"_blank\">View Profile Picture</a>" 
                                        : "";
                                    echo input_h("file", "Profile Picture", "profile_pic", required: empty(user()["profile_pic"]), 
                                        sub_text: $sub_text, attributes: attribute("accept", "image/*"));
                                ?>
                            </div>

                            <!-- Last Name -->
                            <div>
                                <?= input("text", "Last Name", "lastname", user()["lastname"] ?? "", true, placeholder("Enter your last name")); ?>
                            </div>

                            <!-- Other Names -->
                            <div>
                                <?= input("text", "Other Names", "othernames", user()["othernames"] ?? "", true, placeholder("Enter your other names")); ?>
                            </div>

                            <!-- Gender -->
                            <?= select("gender", "Gender", ["male" => "Male", "female" => "Female"], true, value: user()["gender"] ?? ""); ?>

                            <!-- Date of Birth -->
                            <?= input("date", "Date of Birth", "date_of_birth", user()["date_of_birth"] ?? "", true); ?>

                            <!-- Nationality -->
                            <?= select("nationality", "Nationality", nationalities(), true, value: user()["nationality"] ?? "ghanaian"); ?>

                            <!-- Ghana Card -->
                            <?= input("text", "Ghana Card Number", "ghana_card", user()["ghana_card"] ?? "", true, placeholder("GHA-XXXXXXXXX-X")); ?>

                            <!-- Contact Address -->
                            <?= input("text", "Contact Address", "contact_address", user()["contact_address"] ?? "", true, placeholder("House No. / GPS Address / Street, City, Region")); ?>

                            <!-- Phone -->
                            <?= input("tel", "Phone Number", "phone_number", user()["phone_number"] ?? "", true, placeholder("e.g., 0241234567")); ?>

                        </div>
                    <?= fieldset_end(); ?>

                    <!-- PROFESSIONAL DETAILS -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Professional Details"); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                            <!-- Staff ID -->
                            <?= input("text", "Staff ID", "staff_id", user()["staff_id"] ?? "", true, placeholder("Enter staff ID")); ?>

                            <!-- Department -->
                            <?php 
                                $departments = departments(); 
                                $dept_opts = [];
                                foreach($departments as $dept){
                                    $dept_opts[$dept["id"]] = $dept["name"];
                                }
                                echo select("department_id", "Department", $dept_opts, true, value: user()["department_id"] ?? "");
                            ?>

                            <!-- Rank -->
                            <?= select("rank", "Rank", [
                                "Assistant Lecturer" => "Assistant Lecturer",
                                "Lecturer" => "Lecturer",
                                "Senior Lecturer" => "Senior Lecturer",
                                "Associate Professor" => "Associate Professor",
                                "Professor" => "Professor"
                            ], true, value: user()["rank"] ?? ""); ?>

                            <!-- Highest Qualification -->
                            <?= select("qualification", "Highest Qualification", [
                                "PhD" => "PhD",
                                "MPhil" => "MPhil",
                                "MSc" => "MSc",
                                "B.Ed" => "B.Ed",
                                "BSc" => "BSc",
                                "Other" => "Other"
                            ], true, value: user()["qualification"] ?? ""); ?>

                            <!-- Field of Specialization -->
                            <?= input("text", "Field of Specialization", "specialization", user()["specialization"] ?? "", true, placeholder("e.g., Mathematics, Computer Science, English Language")); ?>

                            <!-- Employment Type -->
                            <?= select("employment_type", "Employment Type", [
                                "Full-time" => "Full-time",
                                "Part-time" => "Part-time",
                                "Visiting" => "Visiting"
                            ], true, value: user()["employment_type"] ?? "Full-time"); ?>

                            <!-- Years of Experience -->
                            <?= input("number", "Years of Experience", "years_experience", user()["years_experience"] ?? "", true, attribute("min", 0)); ?>
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- DOCUMENTS -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Academic Documents"); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?= input_h("file", "Curriculum Vitae (CV)", "cv", required: empty(user()["cv"]), attributes: attribute("accept", ".pdf,.doc,.docx")); ?>

                            <?= input_h("file", "Highest Certificate", "certificate", required: empty(user()["certificate"]), attributes: attribute("accept", ".pdf,.jpg,.png")); ?>

                            <?= input_h("file", "Staff ID or National ID", "id_document", required: empty(user()["id_document"]), attributes: attribute("accept", ".pdf,.jpg,.png")); ?>
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- OPTIONAL / ADDITIONAL -->
                    <?= fieldset_start(); ?>
                        <?= fieldset_legend("Additional Information"); ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?= input("text", "Emergency Contact Name", "emergency_name", user()["emergency_name"] ?? "", false, placeholder("Name of person to contact in emergency")); ?>

                            <?= input("tel", "Emergency Contact Number", "emergency_phone", user()["emergency_phone"] ?? "", false, placeholder("024xxxxxxx")); ?>

                            <?= textarea("research_interests", "Research Interests / Short Bio", user()["research_interests"] ?? "", attributes: placeholder("e.g., Artificial Intelligence, Educational Psychology, etc.")); ?>
                        </div>
                    <?= fieldset_end(); ?>

                    <!-- SUBMIT BUTTON -->
                    <div class="mt-4 sm:w-48">
                        <?= button(
                            "submit",
                            empty($teacher()["username"]) ? "Submit Lecturer Details" : "Save Changes",
                            "submit",
                            "save_teacher",
                            "blue",
                        ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        $('.menu-link').on('click', function(e){
            e.preventDefault();
            $('.menu-link').removeClass('active');
            $(this).addClass('active');

            const view = $(this).data('view');
            $('#view-container > div').addClass('hidden');
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
