<?php
require_once relative_path("includes/components.php");

$title = 'My Profile'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex flex-col col-span-1 gap-6 lg:gap-8">
            <!-- Profile Picture Section -->
            <div class="relative p-6 bg-white rounded-lg shadow-md h-max dark:bg-gray-800">
                <div class="relative w-32 h-32 m-auto overflow-hidden rounded-full">
                    <img id="profile-pic" src="<?= asset(user()['profile_pic']) ?>" class="object-cover w-full h-full cursor-pointer" alt="Profile Picture" onclick="$('#file-input').click()">
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

                <!-- other user information -->
                <div class="mt-6 text-center">
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">
                        <?= user()['lastname'] . ' ' . user()['othernames'] ?>
                    </h3>
                    
                    <div class="text-gray-600 dark:text-gray-300">
                        <p class="">
                            <span class="font-medium">
                                <?= $program_name = get_program(user()['program_id'], "name"); ?> | 
                                <?= user()['current_year'] ?>
                            </span>
                        </p>
                        <p class="mt-2">
                            <span class="font-medium">
                                <i class="mr-2 fas fa-bed"></i> <?= get_hall(user()['hall_id'], "name"); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Guardian Information Section -->
            <div class="p-6 bg-white rounded-lg shadow-md h-max dark:bg-gray-800">
                <?php $guardian = guardian(); ?>
                <?= h3("Guardian Information") ?>
                <form action="<?= url("student/submit.php") ?>" method="post">
                    <div class="grid gap-2 md:gap-3 lg:gap-4">
                    <?php echo input("hidden", "", "id", $guardian['id'] ?? 0); ?>
                    <?php echo input("hidden", "", "student_id", user()['student_id']); ?>

                    <!-- Guardian Name -->
                    <?= input("text", "Guardian Name", "name", required: true, value: $guardian['name'] ?? '', attributes: array_merge(
                        placeholder("Enter Guardian's Full Name"), 
                        attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                    ); ?>

                    <!-- Guardian Relationship -->
                    <?= select(
                        "relationship",
                        "Relationship",
                        ["Father", "Mother", "Uncle", "Aunt", "Sibling", "Other"],
                        true,
                        value: $guardian['relationship'] ?? '',
                        required: true
                    ); ?>

                    <!-- Guardian Phone -->
                    <?= input("tel", "Guardian Phone", "phone_number", required: true, value: $guardian['phone_number'] ?? '', attributes: array_merge(
                        placeholder("Enter Guardian's Phone Number"),
                        attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                    ); ?>

                    <!-- Guardian Email -->
                    <?= input("email", "Guardian Email", "email", required: false, value: $guardian['email'] ?? '', attributes: array_merge(
                        placeholder("Enter Guardian's Email"),
                        attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                    ); ?>

                    <!-- Guardian Address -->
                    <?= textarea("address", "Guardian Address", $guardian['address'] ?? '', required: true, attributes: array_merge(
                        placeholder("Enter Guardian's Address"),
                        attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                    ); ?>

                        <!-- Submit Button -->
                        <div class="mt-4">
                            <?= button("submit", "Update Guardian Info", "submit", "save_guardian", "blue", attribute("class", "w-full")) ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        

        <!-- Profile Form Section -->
        <div class="col-span-1 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 lg:col-span-2">
            <form action="<?= url("student/submit.php") ?>" method="post">
                <?= input("hidden", name:"user_id", value: user()["user_id"]) ?>
                <div class="grid gap-2 md:gap-3 lg:gap-4">
                    <!-- Personal Information Fieldset -->
                    <?= fieldset_start(attributes: attribute("class", "grid gap-4 md:grid-cols-2")) ?>
                    <?= fieldset_legend("Personal Information") ?>

                        <!-- Index Number -->
                        <?= input("text", "Index Number", "index_number", required: true, value: user()['index_number'], attributes: array_merge(
                            placeholder("Enter Index Number"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                        <!-- Last Name -->
                        <?= input("text", "Last Name", "lastname", required: true, value: user()['lastname'], attributes: array_merge(
                            placeholder("Enter Last Name"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                        <!-- Other Names -->
                        <?= input("text", "Other Names", "othernames", required: true, value: user()['othernames'], attributes: array_merge(
                            placeholder("Enter Other Names"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                        <!-- Date of Birth -->
                        <?= input("date", "Date of Birth", "date_of_birth", required: true, value: user()['date_of_birth'], attributes: array_merge(
                            attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                        <!-- Gender -->
                        <?= select(
                            text:"Gender", options:[["value" => "male", "text" => "Male"], ["value" => "female", "text" => "Female"]], 
                            required: true, value: user()['gender'], keys: select_keys("value", "text"), 
                            attributes: array_merge(
                                attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"),
                                attribute("disabled")
                            )
                        ); ?>

                    <?= fieldset_end() ?>

                    <!-- Academic information fieldset -->
                    <?= fieldset_start(attributes: attribute("class", "grid gap-4 md:grid-cols-2")) ?>
                    <?= fieldset_legend("Academic Information") ?>
                        <!-- Program Name -->
                        <?= input("text", "Program Name", required: true, value: $program_name, attributes: array_merge(
                            placeholder("Program name"), attribute("readonly"))
                        ); ?>

                        <!-- Current Year -->
                        <?= 
                            select("current_year", "Program Level", [
                                100,200,300,400
                            ], required: true, value: user()["current_year"], attributes: attribute("disabled"));
                        ?>

                        <!-- Enrolment Year -->
                        <?= input("text", "Enrolment Year", "enroled_at", required: true, value: user()['enroled_at'] ?? enrolment_year(user()["current_year"]), attributes: array_merge(
                            placeholder("Enter Enrolment Year"), attribute("readonly"))
                        ); ?>

                        <!-- Expected Completion Year -->
                        <?= input("text", "Expected Completion Year", "completes_at", required: true, value: user()['completes_at'] ?? completion_year(user()["current_year"]), attributes: array_merge(
                            placeholder("Enter Expected Completion Year"), attribute("readonly"))
                        ); ?>

                    <?= fieldset_end() ?>

                    <!-- Contact Information Fieldset -->
                    <?= fieldset_start(attributes: attribute("class", "grid gap-4 md:grid-cols-2")) ?>
                    <?= fieldset_legend("Contact Information") ?>

                        <!-- Email -->
                        <?= input("email", "Email", value: user()['email'], attributes: array_merge(
                            placeholder("Enter Email"), array_merge(
                                attribute("readonly"),
                                attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500")
                            ))
                        ); ?>

                        <!-- Phone Number -->
                        <?= input("text", "Phone Number", "phone_number", required: true, value: user()['phone_number'], attributes: array_merge(
                            placeholder("Enter Phone Number"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                        <!-- Contact Address -->
                        <?= textarea("contact_address", "Contact Address", user()['contact_address'], required: true, attributes: array_merge(
                            placeholder("Enter Contact Address"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                    <?= fieldset_end() ?>

                    <!-- Additional Information Fieldset -->
                    <?= fieldset_start(attributes: attribute("class", "grid gap-4 md:grid-cols-2")) ?>
                    <?= fieldset_legend("Additional Information") ?>
                        <!-- Ghana Card -->
                        <div>
                            <?php echo input_h(
                                "text",
                                "Ghana Card Number",
                                "ghana_card",
                                user()["ghana_card"] ?? '',
                                true,
                                "Include all dashes",
                                array_merge(placeholder("GHA-XXXXXXXXX-X"), attribute("minlength", 6), attribute("required"))
                            ); ?>
                        </div>

                        <!-- Nationality -->
                        <?= select(
                            "nationality",
                            "Nationality",
                            nationalities(),
                            true,
                            value: user()['nationality'],
                            required: true
                        ); ?>

                        <!-- religion -->
                        <?php echo select(
                            "religion",
                            "Religion",
                            ["Christian", "Muslim", "African Traditional"],
                            true,
                            value: user()["religion"] ?? ''
                        ); ?>

                        <!-- Insurance Number -->
                        <?= input_h("text", "Insurance Number", "insurance_number", sub_text: "Valid NHIS or Ghana Card", required: false, value: user()['insurance_number'], attributes: array_merge(
                            placeholder("Enter Insurance Number"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                    <?= fieldset_end() ?>

                    <!-- ezwitch details -->
                    <?= fieldset_start(attributes: attribute("class", "grid gap-4 md:grid-cols-2")) ?>
                    <?= fieldset_legend("Finance Information") ?>

                        <!-- Nationality -->
                        <?= select(
                            "account_bank",
                            "Account Bank",
                            ["Fidelity", "Access Bank", "Ghana Commercial Bank", "Zenith", "Others"],
                            true,
                            value: user()['account_bank'] ?? "",
                            required: true
                        ); ?>

                        <!-- Account Number -->
                        <?= input("text", "E-zwitch Account", "account_number", required: true, value: user()['account_number'] ?? "", attributes: array_merge(
                            placeholder("Enter Account Number"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                    <?= fieldset_end() ?>
                </div>

                <!-- Submit Button -->
                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Save Changes", "submit", "update_student", "blue", attribute("class", "w-full md:wmax-48")) ?>
                </div>
            </form>
        </div>
    </div>
<?php $scripts = <<<HTML
<script>
    $(document).ready(function () {
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

                ajaxCall({
                    url: '/student/submit.php',
                    method: 'POST',
                    sendRaw: true,
                    data: formData
                }).then(response => {
                    if(response.status){
                        original_path = $("#profile-pic").attr('src');
                        $("#cancel-edit").click();
                    }else{
                        alert(response.message);
                    }
                })
            }
        });

        $("#cancel-edit").click(function () {
            $("#profile-pic").attr("src", original_path);
            $('#save-button-container').addClass("hidden").removeClass("flex");
            $('#file-input').val(''); // Clear the file input
        });
    });
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
