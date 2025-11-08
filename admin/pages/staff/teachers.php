<?php
require_once relative_path("includes/components.php");

$title = 'Teachers'; // Set the page title

// Start output buffering to capture the content
ob_start();

// --- 1. DEFINE ROW TEMPLATE ---
// The placeholders must match the keys you'll select in the backend SQL query (e.g., 'fullname', 'email')
$empty_row_template = td_empty("No teachers found", 4);
$row_template = <<<HTML
<template id="teacher-row-template">
    <tr class="text-gray-700 dark:text-gray-400">
        <td class="px-4 py-3 text-sm">__FULLNAME__</td>
        <td class="px-4 py-3 text-sm">__EMAIL__</td>
        <td class="px-4 py-3 text-sm">__GHANA_CARD__</td>
        <td class="px-4 py-3 text-sm">
            <div class='flex items-center gap-2'>
                <i @click='openModal' data-id='__USER_ID__' data-modal-body='delete-body' data-show-footer='1' class='text-red-500 cursor-pointer fas action-btn fa-trash-can hover:text-red-600 action-delete' title='Delete'></i>
                </div>
        </td>
    </tr>
</template>

<template id="empty-row-template">
    $empty_row_template
</template>
HTML;
?>
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
        <?= table_start(attribute("class", "w-full whitespace-no-wrap")) ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th('Name', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Email', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Ghana Card', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Actions', attribute('class', 'px-4 py-3')) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading teachers...", 4) ?>
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
         delete_text: "This will remove all associated records for this admin. Proceed to delete this admin?") ?>
    </div>
<?= modal_end() ?>

<?= $row_template ?>

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
        "USER_ID" => "user_id" // Important for the delete action
    ],
    // 5. Extra Params (The specific submit action)
    ["submit" => "fetch_teachers"]
);

$extra_script = delete_item_component_script();
$scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Initialize pagination on page load
        $pagination_script

        // Existing modal logic
        $(".action-btn").click(function(){
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