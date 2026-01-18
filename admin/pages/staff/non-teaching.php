<?php
require_once relative_path("includes/components.php");

$title = 'Non-Teaching Staff'; // Set the page title
$page_title = 'Non-Teaching Staff';

// Start output buffering to capture the content
ob_start();
?>

<template id="non-teaching-row-template">
    <?= tr_start() ?>
        <td class="hidden" data-id="__ID__"></td> 
        <?= td("__NAME__") ?>
        <?= td("__EMAIL__") ?>
        <?= td("__POSITION__") ?>
        <?= td("__DEPARTMENT__") ?>
        <?= td("__PHONE__") ?>
        <?= td_badge("__STATUS__", "__STATUS_COLOR__") ?>

        <?= td_actions(
            array_merge(
                create_td_action(
                    "fas fa-edit",
                    "Edit",
                    array_merge(
                        attribute("class", "text-blue-500 cursor-pointer hover:text-blue-600 action-edit action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "form-body"),
                        attribute("@click", "openModal")
                    )
                ),
                create_td_action(
                    "fas fa-trash-can",
                    "Delete",
                    array_merge(
                        attribute("class", "text-red-500 cursor-pointer hover:text-red-600 action-delete action-btn"),
                        data_attr("id", "__ID__"),
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
    <?= td_empty("No non-teaching staff found", 7) ?>
</template>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage non-teaching staff members (administrative, support, and other staff)
            </p>
        </div>
        <div class="max-w-96 w-72">
            <?= button("button", "Add Non-Teaching Staff", attributes: array_merge(
                attribute("@click", "openModal"), 
                data_attr("modal-body", "form-body"),
                attribute("id", "add-staff-button"),
                attribute("class", "action-btn")
            )) ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= input("text", "Search", "search", "", false, array_merge(
                attribute("id", "search-staff"),
                attribute("placeholder", "Name, email, or ID"),
                data_attr("filter", "search")
            )) ?>
            
            <?php 
                $departments = fetchData("id, name", "departments", [], 0);
                $dept_options = [["id" => "", "text" => "All Departments"]];
                if(is_array($departments) && !empty($departments)) {
                    foreach($departments as $dept) {
                        $dept_options[] = ["id" => $dept['id'], "text" => $dept['name']];
                    }
                }
            ?>
            <?= select("filter_department", "Department", $dept_options, "All Departments", keys: select_keys("id", "text"), attributes: array_merge(
                attribute("id", "filter-department"),
                data_attr("filter", "department")
            )) ?>
            
            <?php 
                $positions = [
                    ["id" => "", "text" => "All Positions"],
                    ["id" => "registrar", "text" => "Registrar"],
                    ["id" => "bursar", "text" => "Bursar"],
                    ["id" => "librarian", "text" => "Librarian"],
                    ["id" => "accountant", "text" => "Accountant"],
                    ["id" => "secretary", "text" => "Secretary"],
                    ["id" => "clerk", "text" => "Clerk"],
                    ["id" => "security", "text" => "Security"],
                    ["id" => "maintenance", "text" => "Maintenance"]
                ];
            ?>
            <?= select("filter_position", "Position", $positions, "All Positions", attributes: array_merge(
                attribute("id", "filter-position"),
                data_attr("filter", "position")
            )) ?>
        </div>
    </div>

    <div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <?= table_start(); ?>
                <?= thead_start() ?>
                    <?= tr_start() ?>
                        <?= th("Name") ?>
                        <?= th("Email") ?>
                        <?= th("Position") ?>
                        <?= th("Department") ?>
                        <?= th("Phone") ?>
                        <?= th("Status") ?>
                        <?= th("Actions") ?>
                    <?= tr_end() ?>
                <?= thead_end() ?>
                <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                    <?= td_empty("Loading non-teaching staff...", 7) ?> 
                <?= tbody_end() ?>
            <?= table_end(); ?>
        </div>
        
        <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
            <p class="flex items-center col-span-3 gap-2">
                Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> staff
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
</div>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("Add Non-Teaching Staff", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="non-teaching-form" method="POST">
                <?= input("hidden", "", "submit", "add_non_teaching_staff") ?>
                
                <div class="grid gap-4 lg:gap-6">
                    <?= input("email", "Email", "email", required: true, attributes: placeholder("Enter staff email")) ?>
                    
                    <?php 
                        $positions = [
                            ["id" => "registrar", "text" => "Registrar"],
                            ["id" => "bursar", "text" => "Bursar"],
                            ["id" => "librarian", "text" => "Librarian"],
                            ["id" => "accountant", "text" => "Accountant"],
                            ["id" => "secretary", "text" => "Secretary"],
                            ["id" => "clerk", "text" => "Clerk"],
                            ["id" => "security", "text" => "Security"],
                            ["id" => "maintenance", "text" => "Maintenance"],
                            ["id" => "other", "text" => "Other"]
                        ];
                    ?>
                    <?= select("position", "Position", $positions, "Select Position", required: true) ?>
                    
                    <?php 
                        $departments = fetchData("id, name", "departments", [], 0);
                        $dept_options = [["id" => "", "text" => "Select Department"]];
                        if(is_array($departments) && !empty($departments)) {
                            foreach($departments as $dept) {
                                $dept_options[] = ["id" => $dept['id'], "text" => $dept['name']];
                            }
                        }
                    ?>
                    <?= select("department_id", "Department", $dept_options, "Select Department", required: true, keys: select_keys("id", "text")) ?>
                    
                    <?= input("tel", "Phone Number", "phone_number", required: true, attributes: attribute("placeholder", "Enter phone number")) ?>
                    
                    <?= input("password", "Password", "password", required: true, attributes: array_merge(
                        placeholder("Enter password"),
                        attribute("minlength", 8)
                    )) ?>
                </div>

                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Staff", "submit", "add_non_teaching_staff", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("users", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this staff member. Proceed to delete?") ?>
    </div>
<?= modal_end() ?>

<?php 
// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/admin.php',
    'non-teaching-row-template',
    'staff',
    [
        "ID" => "id",             
        "NAME" => "full_name",         
        "EMAIL" => "email",
        "POSITION" => "position",
        "DEPARTMENT" => "department_name",
        "PHONE" => "phone",
        "STATUS" => "status",
        "STATUS_COLOR" => "status_color",
    ],
    ["submit" => "fetch_non_teaching_staff"]
);

$extra_script = delete_item_component_script();
$scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Initialize pagination on page load
        $pagination_script

        // Existing modal control logic
        $(document).on("click", ".action-btn", function(){
            const modal_body = $(this).attr("data-modal-body");
            $("#modal .modal-body").addClass("hidden");
            $("#" + modal_body).removeClass("hidden");
        });

        // Handler for the Add New Staff button
        $("#add-staff-button").click(function(){
            $("#form-body form")[0].reset();
            $("#form-body input[name=email]").attr("readonly", false);
            $("#modal-title").text("Add Non-Teaching Staff");
            $("#form-body button[name=submit]").val("add_non_teaching_staff").html("Add Staff");
        });

        // Handler for Delete action
        $(document).on("click", ".action-delete", function(){
            const id = $(this).attr("data-id");
            $("#delete-body input[name=id]").val(id);
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
?>
