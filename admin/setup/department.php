<?php
require_once relative_path("includes/components.php");

$title = 'Setup Departments'; // Set the page title

// --- Fetch necessary data once ---
// Fetch faculties (used for form, update modal, and select options)
$faculties_data = faculties();
$faculty_options = $faculties_data ? pluck($faculties_data, "id", "name") : [];

// Fetch department heads (used for form and update modal)
$department_heads = department_heads(columns: ["user_id as id, CONCAT(lastname, ' ', othernames) as name"], complete: true);

// Start output buffering to capture the content
ob_start();
?>

<template id="department-row-template">
    <?= tr_start() ?>
        <?= td("__NAME__") ?>
        <?= td("__FACULTY_NAME__", attributes: data_attr("faculty-id", "__FACULTY_ID__")) ?>
        <?= td("__HOD_NAME__", attributes: [data_attr("hod", "__HOD_ID__")]) ?>

        <?= td_actions(
            array_merge(
                create_td_action(
                    "fas fa-pen",
                    "Edit",
                    array_merge(
                        attribute("class", "text-blue-500 cursor-pointer hover:text-blue-600 action-edit action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "update-body"),
                        data_attr("show-footer", "0"),
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
    <?= td_empty("No departments found", 4) ?>
</template>

<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <?= input("text", "Department Name", "name", required: true, attributes: placeholder("Name of the department")); ?>

        <?= select("faculty_id", "Department Faculty", $faculty_options, true, attributes: attribute("class", "w-full")); ?>

        <?php if($department_heads): ?>
            <?= select(
                "hod", "Head of Department", $department_heads, keys: select_keys("id", "name"), 
                nullable: "Select A Department Head",
                attributes: attribute("class", "w-full")
            ); ?>
        <?php endif; ?>
    <?= form_body_end() ?>

    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Department", "submit", "create_department", "blue") ?>
    </div>
</form>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(); ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Name of Department") ?>
                    <?= th("Faculty") ?>
                    <?= th("Head of Department") ?>
                    <?= th("Actions") ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading departments...", 4) ?> 
            <?= tbody_end() ?>
        <?= table_end(); ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> departments
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
    <div id="update-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("Update Department Info") ?>
            
            <form action="<?= url("admin/submit.php") ?>" method="POST">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <?= input("text", "Department Name", "name", required: true, attributes: placeholder("Name of the department")); ?>

                    <?= select("faculty_id", "Department Faculty", $faculty_options, true); ?>

                    <?php if(isset($department_heads) && $department_heads): ?>
                        <?= select(
                            "hod", "Head of Department", $department_heads, keys: select_keys("id", "name"), 
                            nullable: "Select A Department Head",
                            attributes: attribute("class", "w-full")
                            ); ?>
                    <?php endif; ?>

                    <?= input("hidden", name: "department_id") ?>
                </div>

                <div class="mt-4">
                    <?= button("submit", "Update", "submit", "update_department") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("departments", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated courses and other records for this page. Proceed to delete this department?") ?>
    </div>
<?= modal_end() ?>

<?php 
// ==============================================
// 5. SCRIPTS
// ==============================================

// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/school.php',    // Target AJAX file (you need to create this)
    'department-row-template',      // Template ID
    'departments',                  // Data key in backend response ($data["departments"])
    [
        "ID" => "id",             
        "NAME" => "name",         
        "FACULTY_ID" => "faculty_id",     // Faculty ID (for select)
        "FACULTY_NAME" => "faculty_name", // Faculty Name (for display)
        "HOD_ID" => "hod",                // HOD user ID (for select)
        "HOD_NAME" => "hod_name",         // HOD name (for display)
    ],
    ["submit" => "fetch_departments"] // The action to trigger
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

        // REFACTORED: Handle action-edit click to populate the update modal
        $(document).on("click", ".action-edit", function(){
            const row = $(this).closest("tr");
            
            // Get data from dynamic row
            const id = $(this).attr("data-id");
            const name = $.trim(row.find("td:nth-child(1)").text());
            // Data attributes are used to fetch the IDs
            const faculty = row.find("td:nth-child(2)").data("faculty-id") || "";
            const hod = row.find("td:nth-child(3)").data("hod") || "";

            // Populate the modal form fields
            $("#update-body input[name=name]").val(name);
            $("#update-body select[name=faculty_id]").val(faculty).change();
            $("#update-body select[name=hod]").val(hod).change();
            $("#update-body input[name=department_id]").val(id);
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