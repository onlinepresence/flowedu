<?php
require_once relative_path("includes/components.php");

$title = 'Courses'; // Set the page title
$program_id = user()["program_id"];

// Start output buffering to capture the content
ob_start();
?>

<template id="course-row-template">
    <?= tr_start(
        array_merge(
            data_attr("program-id", "__PROGRAM_ID__"), // Used for select update
            data_attr("semester", "__SEMESTER_ID__"),   // Used for select update
            data_attr("year-level", "__LEVEL_ID__")     // Used for select update
        )
    ) ?>
        <?= td("__NAME__", attributes: array_merge(
            data_attr("name", "__NAME__"),
            attribute("class", "name")
        )) ?>
        <?= td("__CODE__", attributes: array_merge(
            data_attr("code", "__CODE__"),
            attribute("class", "code")
        )) ?>
        <?= td("N/A") ?>
    <?= tr_end() ?>
</template>

<template id="empty-row-template">
    <?= td_empty("No courses found", 4) ?>
</template>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(); ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Course Name") ?>
                    <?= th("Course Code") ?>
                    <?= th("Lecturer") ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading courses...", 4) ?> 
            <?= tbody_end() ?>
        <?= table_end(); ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> courses
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

<?php 
// ==============================================
// 5. SCRIPTS
// ==============================================

// Set up the pagination script call
$pagination_script = pagination_script(
    'student/ajax/courses.php',    // Target AJAX file (you need to create this)
    'course-row-template',      // Template ID
    'courses',                  // Data key in backend response ($data["courses"])
    [
        "ID" => "id",             
        "NAME" => "name",         
        "CODE" => "code",
        "PROGRAM_ID" => "program_id",
        "PROGRAM_NAME" => "program_name",
        "SEMESTER_ID" => "course_semester", // maps to data-semester
        "LEVEL_ID" => "year_level",         // maps to data-year-level
    ],
    ["submit" => "fetch_my_courses", "program_id" => $program_id] // The action to trigger
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

        // Handler for the Add New Course button (Reset form for creation)
        $("#add-course-button").click(function(){
            $("#form-body form")[0].reset();
            $("#form-body input[name=course_id]").val("");
            
            // Set fields to editable for new entry
            $("#form-body input[name=code]").attr("readonly", false);
            $("#form-body input[name=name]").attr("readonly", false);

            $("#form-body select[name=year_level]").removeClass("pointer-events-none bg-gray-100");
            
            // Reset titles and button for ADD operation
            $("#modal-title").text("Add New Course");
            $("#form-body button[name=submit]").val("create_course").html("Add Course");
        });

        // Handler for Edit action
        $(document).on("click", ".action-edit", function(){
            $("#modal-title").text("Update Course");
            $("#form-body button[name=submit]").val("update_course").html("Update Course");

            const parent = $(this).closest("tr");
            
            // Extract data from the row (Name and Code are stored in their respective TD data attributes)
            const id = $(this).attr("data-id");
            const name = parent.find('td.name').data('name');
            const code = parent.find('td.code').data('code');

            // Extract hidden data from the Program Name TD
            const program_id = parent.data('program-id') || "";
            const course_semester = parent.data('semester') || "";
            const year_level = parent.data('year-level') || "";

            // Populate the modal form fields
            $("#form-body input[name=name]").val(name).attr("readonly", true); // Should be read-only on edit
            $("#form-body input[name=code]").val(code).attr("readonly", true); // Should be read-only on edit
            $("#form-body select[name=program_id]").val(program_id).change();
            $("#form-body select[name=course_semester]").val(course_semester).change();
            $("#form-body select[name=year_level]").val(year_level).change().addClass("pointer-events-none bg-gray-100");
            $("#form-body input[name=course_id]").val(id);
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