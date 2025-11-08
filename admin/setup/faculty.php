<?php
require_once relative_path("includes/components.php");

$title = 'Setup Faculties'; // Set the page title

// Start output buffering to capture the content
ob_start();

// get all deans (used for the form and update modal)
$deans = deans(columns: ["user_id as id, CONCAT(lastname, ' ', othernames) as name"], complete: true);
?>

<template id="faculty-row-template">
    <?= tr_start() ?>
        <?= td("__NAME__") ?>
        <?= td("__DEAN_NAME__", attributes: [data_attr("dean-id", "__DEAN_ID__")]) ?>

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
    <?= td_empty("No faculties found", 3) ?>
</template>

<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <?= input("text", "Faculty Name", "name", required: true, attributes: array_merge(
            placeholder("Faculty of Arts"), attribute("class", "w-full"))
        ); ?>

        <?php if($deans): ?>
            <?= select(
                "dean_id", "Faculty Dean", $deans, keys: select_keys("id", "name"), 
                nullable: "Select A Faculty Dean",
                attributes: array_merge(
                    placeholder("Select Dean"), attribute("class", "w-full"))
            ); ?>
        <?php endif; ?>
    <?= form_body_end() ?>

    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Faculty", "submit", "create_faculty", "blue") ?>
    </div>
</form>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Name of Faculty") ?>
                    <?= th("Name of Dean") ?>
                    <?= th("Actions") ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading faculties...", 3) ?> 
            <?= tbody_end() ?>
        <?= table_end(); ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> faculties
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
            <?= modal_title("Update Faculty Info") ?>
            
            <form action="<?= url("admin/submit.php") ?>" method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <?= input("text", "Faculty Name", "name", required: true, attributes: array_merge(
                        placeholder("Faculty of Arts"), attribute("class", "w-full"))
                    ); ?>

                                        <?= input("hidden", name: "faculty_id") ?>

                                        <?php if(isset($deans) && !empty($deans)): ?>
                        <?= select(
                            "dean_id", "Dean", $deans, required: true, keys: select_keys("id", "name"), 
                            nullable: "Select A Faculty Dean",
                            attributes: array_merge(
                                placeholder("Select Dean"), attribute("class", "w-full"))
                        ); ?>
                    <?php endif; ?>
                </div>

                                 <div class="mt-4">
                    <?= button("submit", "Update", "submit", "update_faculty") ?>
                 </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

        <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("faculties", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated departments and courses. Proceed to delete this faculty?") ?>
    </div>
<?= modal_end() ?>

<?php 
// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/school.php',     // Target AJAX file
    'faculty-row-template',       // Template ID
    'faculties',                  // Data key in backend response ($data["faculties"])
    [
        "ID" => "id",             // Primary key for actions
        "NAME" => "name",         // Faculty Name
        "DEAN_NAME" => "dean_name",// Dean's full name
        "DEAN_ID" => "dean_id"    // Dean's user ID for the form select
    ],
    ["submit" => "fetch_faculties"] // The action to trigger
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

        // REFACTORED: Handle action-edit click to populate the update modal
        $(document).on("click", ".action-edit", function(){
            // Find data directly from the dynamic row data attributes
            const row = $(this).closest("tr");
            
            // Get data from placeholders' rendered values (data-id, td content)
            const id = $(this).attr("data-id");
            const name = row.find('td:nth-child(1)').text(); // Faculty name is in the first column
            const dean = row.find('td:nth-child(2)').data("dean-id") || ""; // Dean ID is in data-dean-id attribute

            $("#update-body input[name=name]").val($.trim(name));
            $("#update-body select[name=dean_id]").val(dean).change();
            $("#update-body input[name=faculty_id]").val(id);
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