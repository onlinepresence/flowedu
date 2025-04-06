<?php 
    if(!isset($is_student)){
        $is_student = false;
    }
?>

<div class="grid grid-cols-1 gap-4" id="student-form-grid">
    <!-- Hidden User ID -->
    <?php echo input("hidden", "", "user_id", $is_student ? $_SESSION['user_id'] : "", true); ?>

    <?= fieldset_start(); ?>
        <?= fieldset_legend("Student Information") ?>
        <?php if($is_student): ?>
            <?= information_bar(
                "Your profile picture should be minimum of minimum size 300 x 400 (7:9 ratio), and should have a red solid background. If your image does not meet this standard, it shall be rejected",
                attributes: attribute("class", "mb-4 text-sm rounded-sm")
                ) ?>
        <?php endif; ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 <?= $is_student? "lg:grid-cols-3" : "" ?> gap-6">
            <!-- index number -->
            <div>
                <?php echo input(
                    "text",
                    "Index Number",
                    "index_number",
                    $is_student ? ($user["index_number"] ?? generate_admission_index()) : "",
                    $is_student,
                    array_merge(attribute("readonly", ""))
                ); ?>
            </div>

            <!-- profile picture -->
            <div>
                <?php
                    $sub_text = $is_student && user()["profile_pic"] ? 
                                "<a href=\"".asset(user()['profile_pic'])."\" target=\"_blank\">View Profile Picture</a>" :
                                "";
                    echo input_h("file", "Profile Picture", "profile_pic", required: $is_student && empty(user()["profile_pic"]), sub_text: $sub_text, attributes: attribute("accept", "image/*"))
                ?>
            </div>

            <?php if($is_student): ?>
            <!-- username -->
            <div>
                <?php echo input(
                    "text",
                    "Username",
                    "username",
                    $is_student ? ($user["username"] ?? '') : "",
                    $is_student,
                    placeholder("Username")
                ); ?>
            </div>
            <?php endif; ?>

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
                    $is_student ? ($user["date_of_birth"] ?? '') : "",
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
                value: $is_student ? $user["gender"] : "",
                required: true
            ); ?>

            <!-- nationality -->
            <?php echo select(
                "nationality",
                "Nationality",
                nationalities(),
                true,
                value: $is_student ? ($user["nationality"] ?? 'ghanaian') : "",
                required: true
            ); ?>

            <!-- religion -->
            <?php echo select(
                "religion",
                "Religion",
                ["Christian", "Muslim", "African Traditional"],
                true,
                value: $is_student ? ($user["religion"] ?? '') : ""
            ); ?>

            <!-- contact address -->
            <div>
                <?php echo input(
                    "text",
                    "Home/GPS Address",
                    "contact_address",
                    $is_student ? ($user["contact_address"] ?? '') : "",
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
                    $is_student ? ($user["phone_number"] ?? '') : "",
                    true,
                    placeholder("Phone Number")
                ); ?>
            </div>
        </div>
    <?= fieldset_end(); ?>

    <!-- health information details -->
    <?= fieldset_start() ?>
        <?= fieldset_legend("Health Information") ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 <?= $is_student ? "lg:grid-cols-3" : "" ?> gap-6">
            <div>
                <?php echo input(
                    "text",
                    "Health Insurance Number",
                    "insurance_number",
                    $is_student ? ($user["insurance_number"] ?? '') : "",
                    true,
                    array_merge(placeholder("Health insurance number"), attribute("minlength", 6))
                ); ?>
            </div>

            <div>
                <?= 
                    textarea("allergy", "Allergies", $is_student ? (user()["allergy"] ?? '') : "", 
                    attributes: placeholder("Severe peanut allergy. Allergic to corn meals"));
                ?>
            </div>
        </div>            
    <?= fieldset_end() ?>

    <!-- academic information details -->
    <?= fieldset_start(); ?>
        <?= fieldset_legend("Academic Information") ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 <?= $is_student ? "lg:grid-cols-3" : "" ?> gap-6">
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

                    echo select("program_id", "Select a Program", $programs_, true, required: true, value: $is_student ? (user()["program_id"] ?? '') : "");
                    echo input("hidden", name: "department_id", value: $is_student ? (user()['department_id'] ?? '') : "");
                ?>
            </div>

            <?php if($is_student): ?>
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
            <?php endif; ?>

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

                    echo select("hall_id", "Select a Hall", $halls_, true, required: true, value: $is_student ? (user()["program_id"] ?? '') : "")
                ?>
            </div>

            <?php if($is_student): ?>
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

            <div>
                <?= 
                    select("current_year", "Program Year", [
                        100,200,300,400
                    ], required: true, value: $is_student ? $user["current_year"] ?? "" : "")
                ?>
            </div>
            <?php endif; ?>
        </div>
    <?= fieldset_end(); ?>
</div>