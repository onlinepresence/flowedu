<?php
require_once relative_path("includes/components.php");

$title = 'Personal Details'; // Set the page title
$user = user();

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("student/submit.php") ?>" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <div class="grid grid-cols-1 gap-4">
        <!-- Hidden User ID -->
        <?php echo input("hidden", "", "user_id", $_SESSION['user_id'], true); ?>

        <?= fieldset_start(); ?>
            <?= fieldset_legend("Student Information") ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- index number -->
                <div>
                    <?php echo input(
                        "text",
                        "Index Number",
                        "index_number",
                        $user["index_number"] ?? generate_admission_index(),
                        true,
                        array_merge(attribute("readonly", ""))
                    ); ?>
                </div>

                <!-- profile picture -->
                <div>
                    <?php
                        $sub_text = user()["profile_pic"] ? 
                                    "<a href=\"".asset(user()['profile_pic'])."\" target=\"_blank\">View Profile Picture</a>" :
                                    "";
                        echo input_h("file", "Profile Picture", "profile_pic", required: empty(user()["profile_pic"]), sub_text: $sub_text, attributes: attribute("accept", "image/*"))
                    ?>
                </div>

                <!-- username -->
                <div>
                    <?php echo input(
                        "text",
                        "Username",
                        "username",
                        $user["username"] ?? '',
                        true,
                        placeholder("Username")
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

                <!-- date of birth -->
                <div>
                    <?php echo input(
                        "date",
                        "Date of Birth",
                        "date_of_birth",
                        $user["date_of_birth"] ?? '',
                        true
                    ); ?>
                </div>

                <!-- gender -->
                <?php echo select(
                    "gender",
                    "Gender",
                    [
                        "male" => "Male",
                        "female" => "Female"
                    ],
                    true,
                    value: $user["gender"],
                    required: true
                ); ?>

                <!-- nationality -->
                <?php echo select(
                    "nationality",
                    "Nationality",
                    nationalites(),
                    true,
                    value: $user["nationality"] ?? 'ghanaian',
                    required: true
                ); ?>

                <!-- religion -->
                <?php echo select(
                    "religion",
                    "Religion",
                    ["Christian", "Muslim", "African Traditional"],
                    true,
                    value: $user["religion"] ?? ''
                ); ?>

                <!-- contact address -->
                <div>
                    <?php echo input(
                        "text",
                        "Home/GPS Address",
                        "contact_address",
                        $user["contact_address"] ?? '',
                        true,
                        placeholder("H.No 12, Atomic Street, East Legon, Accra, Greater Accra")
                    ); ?>
                </div>

                <!-- phone number -->
                <div>
                    <?php echo input(
                        "tel",
                        "Phone Number",
                        "phone_number",
                        $user["phone_number"] ?? '',
                        true,
                        placeholder("Phone Number")
                    ); ?>
                </div>
            </div>
        <?= fieldset_end(); ?>

        <!-- health information details -->
        <?= fieldset_start() ?>
            <?= fieldset_legend("Health Information") ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <?php echo input(
                        "text",
                        "Health Insurance Number",
                        "insurance_number",
                        $user["insurance_number"] ?? '',
                        true,
                        array_merge(placeholder("Health insurance number"), attribute("minlength", 6))
                    ); ?>
                </div>

                <div>
                    <?= 
                        textarea("allergy", "Allergies", user()["allergy"] ?? '', 
                        attributes: placeholder("Severe peanut allergy. Allergic to corn meals"));
                    ?>
                </div>
            </div>            
        <?= fieldset_end() ?>

        <!-- academic information details -->
        <?= fieldset_start(); ?>
            <?= fieldset_legend("Academic Information") ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <?php 
                        $programs = programs(); 
                        $programs_ = [];
                        foreach($programs as $program){
                            $programs_[$program["id"]] = [
                                "text" => $program["name"],
                                "attr" => array_merge(
                                    data_attr("dept-id", $program["department_id"]),
                                    data_attr("cert", $program["certificate"]),
                                    data_attr("cost", number_format($program["cost"], 2))
                                )
                            ];
                        }

                        echo select("program_id", "Select a Program", $programs_, true, required: true, value: user()["program_id"] ?? '');
                        echo input("hidden", name: "department_id", value: user()['department_id'] ?? '');
                    ?>
                </div>

                <!-- show the program certificate -->
                <div>
                    <?= 
                        input(label: "Program Certification", attributes: array_merge(
                            attribute("readonly"), attribute("id", "program_certificate"), placeholder("Program Certification")
                        ));
                    ?>
                </div>

                <!-- show program cost -->
                <div>
                    <?= 
                        input(label: "Program Cost", attributes: array_merge(
                            attribute("readonly"), attribute("id", "program_cost"), placeholder("GHC 0.00")
                        ));
                    ?>
                </div>

                <!-- hall -->
                <div>
                    <?php 
                        $halls = halls(); 
                        $halls_ = [];
                        foreach($halls as $hall){
                            $halls_[$hall["id"]] = [
                                "text" => $hall["name"],
                                "attr" => array_merge(
                                    data_attr("cost", number_format($hall["cost"], 2)),
                                    data_attr("period", format_hall_period($hall["period"])),
                                )
                            ];
                        }

                        echo select("hall_id", "Select a Hall", $halls_, true, required: true, value: user()["program_id"] ?? '')
                    ?>
                </div>

                <!-- show the hall cost -->
                <div>
                    <?= 
                        input(label: "Hall Cost", attributes: array_merge(
                            attribute("readonly"), attribute("id", "hall_cost"), placeholder("GHC 0.00")
                        ));
                    ?>
                </div>

                <!-- show hall cost period -->
                <div>
                    <?= 
                        input(label: "Cost Period", attributes: array_merge(
                            attribute("readonly"), attribute("id", "hall_period"), placeholder("Cost Period")
                        ));
                    ?>
                </div>
            </div>
        <?= fieldset_end(); ?>
    </div>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", empty(user()["username"]) ? "Submit Personal Details" : "Save Changes", "submit", "create_student", "blue", user()["approved"] ? attribute("disabled") : []) ?>
    </div>
</form>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        $("select[name=program_id]").change(function(){
            const val = $(this).val();

            if(val != ""){
                const option = $(this).find("option:selected");
                const certificate = option.attr("data-cert");
                const department_id = option.attr("data-dept-id");
                const cost = option.attr("data-cost");

                $("input[name=department_id]").val(department_id);
                $("#program_certificate").val(certificate);
                $("#program_cost").val("GHC " + cost);
            }else{
                $("input[name=department_id], #program_certificate, #program_cost").val("");
            }
        })

        $("select[name=hall_id]").change(function(){
            const val = $(this).val();

            if(val != ""){
                const option = $(this).find("option:selected");
                const cost = option.attr("data-cost");
                const period = option.attr("data-period");

                $("#hall_period").val(period);
                $("#hall_cost").val("GHC " + cost);
            }
        })

        // make selection cases
        $("select[name=hall_id], select[name=program_id]").change();

        // make uneditable if approved
        if($("button[name=submit]").prop("disabled") === true){
            $("input, textarea").attr("readonly", true);
            $("select").prop("disabled", true);
        }
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
