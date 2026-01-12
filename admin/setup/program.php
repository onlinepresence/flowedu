<?php
require_once relative_path("includes/components.php");

$title = 'Setup Programs'; // Set the page title

// --- Fetch necessary data once ---
// Fetch departments (used for form and update modal)
$departments_data = departments();
$department_options = $departments_data ? pluck($departments_data, "id", "name") : ["" => "No Departments created"];

// Start output buffering to capture the content
ob_start();
?>

<template id="program-row-template">
    <?= tr_start() ?>
        <?= td("__NAME__") ?>
        <?= td("__CERTIFICATE__") ?>
        <?= td("GHC __COST__") ?>
        <?= td("__DEPARTMENT_NAME__", attributes: [data_attr("department-id", "__DEPARTMENT_ID__")]) ?>

        <?= td_actions(
            array_merge(
                create_td_action(
                    "fas fa-pen",
                    "Edit",
                    array_merge(
                        attribute("class", "text-blue-500 cursor-pointer hover:text-blue-600 action-edit action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "form-body"),
                        data_attr("show-footer", "0"),
                        attribute("@click", "openModal")
                    )
                ),
                create_td_action(
                    "fas fa-server",
                    "Manage Courses",
                    array_merge(
                        attribute("class", "text-green-500 cursor-pointer hover:text-green-600"),
                        attribute("href", route("program.classes", ["program_id" => "__ID__"]))
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
    <?= td_empty("No programs found", 5) ?>
</template>

<div class="max-w-96 w-72">
    <?= button("button", "Add New Program", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-program-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(); ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Name of Program") ?>
                    <?= th("Certification") ?>
                    <?= th("Cost of Program") ?>
                    <?= th("Department") ?>
                    <?= th("Actions") ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading programs...", 5) ?> 
            <?= tbody_end() ?>
        <?= table_end(); ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> programs
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
            <?= modal_title("Add New Program", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="program-form" method="POST">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    
                    <?= input("hidden", name: "program_id") ?>

                    <?= input("text", "Program Name", "name", required: true, attributes: placeholder("Name of the program")); ?>

                    <?= input("number", "Program Fee", "cost", required: true, attributes: array_merge(placeholder("0.00"), attribute("step", 0.01))); ?>
                    
                    <?= input("text", "Program Certification", "certificate", required: true, attributes: placeholder("Eg. Bachelor of Education (B.Ed)")); ?>

                    <?= select("department_id", "Program Department", $department_options, required: true) ?>
                    
                    <?= input("text", "Program Length", "program_length", 4, attributes: placeholder("Eg. Bachelor of Education (B.Ed)")); ?>
                </div>

                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Program", "submit", "create_program", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("programs", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this course program. Proceed to delete this program?") ?>
    </div>
<?= modal_end() ?>

<?php 
// ==============================================
// 5. SCRIPTS
// ==============================================

// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/school.php',    // Target AJAX file (you need to create this)
    'program-row-template',      // Template ID
    'programs',                  // Data key in backend response ($data["programs"])
    [
        "ID" => "id",             
        "NAME" => "name",         
        "CERTIFICATE" => "certificate", 
        "COST" => "cost",                 
        "DEPARTMENT_ID" => "department_id",     // Department ID (for select)
        "DEPARTMENT_NAME" => "department_name", // Department Name (for display)
    ],
    ["submit" => "fetch_programs"] // The action to trigger
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

        // Handler for the Add New Program button
        $("#add-program-button").click(function(){
            // Reset form for ADD operation
            $("#form-body form")[0].reset();
            $("#form-body input[name=program_id]").val("");
            $("#form-body select[name=department_id]").val("").change(); 

            $("#modal-title").text("Add New Program");
            $("#form-body button[name=submit]").val("create_program").html("Add Program");
        });

        // Handler for Edit action
        $(document).on("click", ".action-edit", function(){
            $("#modal-title").text("Update Program");
            $("#form-body button[name=submit]").val("update_program").html("Update Program");

            const parent = $(this).closest("tr");
            
            // Extract data from the row
            const id = $(this).attr("data-id");
            const name = $.trim(parent.find("td:nth-child(1)").text());
            const certificate = $.trim(parent.find("td:nth-child(2)").text());
            
            // Clean up the cost value (remove 'GHC ' and commas)
            const costText = $.trim(parent.find("td:nth-child(3)").text());
            const cost = costText.replace("GHC ", "").replace(/,/g, ""); 
            
            const department = parent.find("td:nth-child(4)").data("department-id") || "";

            // Populate the modal form fields
            $("#form-body input[name=name]").val(name);
            $("#form-body input[name=certificate]").val(certificate);
            $("#form-body input[name=cost]").val(cost);
            $("#form-body select[name=department_id]").val(department).change();
            $("#form-body input[name=program_id]").val(id);
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