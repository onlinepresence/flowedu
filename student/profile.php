<?php
require_once relative_path("includes/components.php");

$title = 'My Profile'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Profile Picture Section -->
        <div class="col-span-1 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <div class="relative w-32 h-32 m-auto overflow-hidden rounded-full">
                <img id="profile-pic" src="<?= asset(user()['profile_pic']) ?>" class="object-cover w-full h-full cursor-pointer" alt="Profile Picture" onclick="$('#file-input').click()">
                <input type="file" id="file-input" class="hidden" accept="image/*">
            </div>
            <div class="mt-6 text-center" id="save-button-container" style="display: none;">
                <button id="save-button" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">Save</button>
                <button id="cancel-edit" type="button" class="px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600">Cancel</button>
            </div>
        </div>

        <!-- Profile Form Section -->
        <div class="col-span-1 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 lg:col-span-2">
            <form action=""></form>
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
                            "gender", "Gender", [["value" => "male", "text" => "Male"], ["value" => "female", "text" => "Female"]], 
                            required: true, value: user()['gender'], keys: select_keys("value", "text"), 
                            attributes: array_merge(attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                    <?= fieldset_end() ?>

                    <!-- Contact Information Fieldset -->
                    <?= fieldset_start(attributes: attribute("class", "grid gap-4 md:grid-cols-2")) ?>
                    <?= fieldset_legend("Contact Information") ?>

                        <!-- Email -->
                        <?= input("email", "Email", "email", required: true, value: user()['email'], attributes: array_merge(
                            placeholder("Enter Email"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
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
                        <?= input("text", "Insurance Number", "insurance_number", required: false, value: user()['insurance_number'], attributes: array_merge(
                            placeholder("Enter Insurance Number"), attribute("class", "w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"))
                        ); ?>

                    <?= fieldset_end() ?>
                </div>

                <!-- Submit Button -->
                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Save Changes", "submit", "update_student", "blue", attribute("disabled")) ?>
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
                $('#save-button-container').show();
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
            $('#save-button-container').hide();
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
