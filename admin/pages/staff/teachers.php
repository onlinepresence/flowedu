<?php
require_once relative_path("includes/components.php");

$title = 'Teachers'; // Set the page title

// Start output buffering to capture the content
ob_start();

// get all departments
$departments = departments();
?>

<template id="teacher-row-template">
    <?= tr_start() ?>
        <!-- Name + Profile Picture -->
        <?= td("__FULLNAME__", "__PROFILE_PIC__") ?>
        <!-- Email -->
        <?= td("__EMAIL__") ?>
        <!-- Phone -->
        <?= td("__PHONE__") ?>
        <!-- Staff ID -->
        <?= td("__STAFF_ID__") ?>
        <!-- Department -->
        <?= td("__DEPARTMENT__") ?>
        <!-- Employment Type -->
        <?= td("__EMPLOYMENT_TYPE__") ?>

        <!-- Actions -->
        <?= td_actions(
            array_merge(
                create_td_action(
                    "fas fa-eye",
                    "View",
                    array_merge(
                        attribute("class", "text-blue-500 cursor-pointer hover:text-blue-600 action-view action-btn"),
                        data_attr("id", "__USER_ID__"),
                        data_attr("modal-body", "view-teacher"),
                        data_attr("show-footer", "0"),
                        attribute("@click", "openModal")
                    )
                ),
                create_td_action(
                    "fas fa-edit",
                    "Edit",
                    array_merge(
                        attribute("class", "text-yellow-500 cursor-pointer hover:text-yellow-600 action-edit action-btn"),
                        data_attr("id", "__USER_ID__"),
                        data_attr("modal-body", "edit-teacher"),
                        data_attr("show-footer", "1"),
                        attribute("@click", "openModal")
                    )
                ),
                create_td_action(
                    "fas fa-trash-can",
                    "Delete",
                    array_merge(
                        attribute("class", "text-red-500 cursor-pointer hover:text-red-600 action-delete action-btn"),
                        data_attr("id", "__USER_ID__"),
                        data_attr("modal-body", "delete-body"),
                        data_attr("show-footer", "1"),
                        attribute("@click", "openModal")
                    )
                )
            )
        ) ?>

    <?= tr_end() ?>
</template>


<template id="empty-row-template">
    <?= td_empty("No teachers found", 7) ?>
</template>

<div class="max-w-96 w-72">
    <?= button("button", "Add New Teacher", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-teacher-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= tr_start(attribute("class", "whitespace-no-wrap")) ?>
                    <?= th('Name', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Email', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Phone', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Staff ID', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Department', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Employment Type', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Actions', attribute('class', 'px-4 py-3')) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading teachers...", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> teachers
        </p>
        <span class="col-span-2"></span>
        <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
            <nav aria-label="Table navigation">
                <ul id="pagination" class="inline-flex items-center">
                    <li>
                        <button id="prev-page" class="px-3 py-1 rounded-md rounded-l-lg focus:outline-none focus:shadow-outline-purple" aria-label="Previous">
                            <svg aria-hidden="true" class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path>
                            </svg>
                        </button>
                    </li>
                    <li>
                        <button id="next-page" class="px-3 py-1 rounded-md rounded-r-lg focus:outline-none focus:shadow-outline-purple" aria-label="Next">
                            <svg class="w-4 h-4 fill-current" aria-hidden="true" viewBox="0 0 20 20">
                                <path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path>
                            </svg>
                        </button>
                    </li>
                </ul>
            </nav>
        </span>
    </div>
</div>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
        <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="admin-form" method="POST">
                <div class="grid gap-4 lg:gap-6">
                    <?= input("email", "Teacher Email", "email", required: true, attributes: placeholder("Enter admin email")); ?>
                    <?= select("department_id", "Teacher's Main Department", $departments, "Select Department", required: true, keys: select_keys(text: "name")) ?>
                    <?= input_h("text", "Staff ID", "staff_id", sub_text: "Leave blank so that the teacher provides it himself",  attributes: placeholder("Enter Teacher's staff ID")); ?>
                    <?= hidden_input("type", "teacher") ?>
                    <?= input_h("password", "Password", "password", sub_text: "You can leave this blank and we’ll create a random password for you.", attributes: array_merge(
                        placeholder("Enter password"), attribute("minlength", 8)
            )); ?>
                </div>

                                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Teacher", "submit", "add_user", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("teachers", form_action: url("admin/submit.php"), 
         delete_text: "This will remove all associated records for this teacher. Proceed to delete?") ?>
    </div>
<?= modal_end() ?>

<?php 
// --- 3. IMPLEMENT PAGINATION SCRIPT ---
$pagination_script = pagination_script(
    // 1. AJAX File Path (We'll assume you create this)
    'admin/ajax/teacher.php', 
    // 2. Template ID
    'teacher-row-template', 
    // 3. Data Key (Must match key in backend response, e.g., $data["teachers"])
    'teachers', 
    // 4. Template Placeholder Mapping (Placeholder => Data Key)
    [
        "FULLNAME" => "fullname", 
        "EMAIL" => "email", 
        "GHANA_CARD" => "ghana_card",
        "USER_ID" => "user_id", // Important for the delete action
        "PHONE" => "phone",
        "STAFF_ID" => "staff_id",
        "DEPARTMENT" => "department",
        "EMPLOYMENT_TYPE" => "employment_type",
        "PROFILE_PIC" => "profile_pic"
    ],
    // 5. Extra Params (The specific submit action)
    ["submit" => "fetch_teachers"],
    ["profile_pic"]
);

$extra_script = delete_item_component_script();
$scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Initialize pagination on page load
        $pagination_script

        // Existing modal logic
        $(document).on("click", ".action-btn", function(){
            const modal_body = $(this).attr("data-modal-body");
            $("#modal .modal-body").addClass("hidden");
            $("#" + modal_body).removeClass("hidden");
        });

        $("#add-teacher-button").click(function(){
            $("#form-body input[name=email]").val("").attr("readonly", false);
            $("#form-body input[name=password]").val("");
            
            $("#modal-title").text("Add New Teacher");
            $("#form-body button[name=submit]").val("add_user").html("Add Teacher");
        });

        $extra_script
    })
</script>
HTML;
?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');