<?php
require_once relative_path("includes/components.php");

$program = programs($program_id);

$title = $program['name'] . (isset($form_level) ? ' Courses' : ""); // Set the page title
$page_title = isset($form_level) ? "Courses for {$program['name']} Level ".($form_level * 100) : "{$program['name']}: Manage Program Structure";

// Define static options for course level and semester
$course_semesters = [
    ["id" => 1, "text" => "Semester 1"],
    ["id" => 2, "text" => "Semester 2"]
];

$year_levels = [
    ["id" => 1, "text" => "Year 1"],
    ["id" => 2, "text" => "Year 2"],
    ["id" => 3, "text" => "Year 3"],
    ["id" => 4, "text" => "Year 4"],
];


// Start output buffering to capture the content
ob_start();
?>

<?php if(!isset($form_level)): ?>
    <!-- Page Header -->
<div class="mb-10">
    <p class="mt-2 text-gray-600 dark:text-gray-400">
        Select an academic year and choose what you want to manage for the 
        <span class="font-medium text-gray-900 dark:text-gray-200">
            <?= $program["name"] ?>
        </span> program.
    </p>
</div>

<!-- Program Years Grid -->
<div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    <?php for ($level = 1; $level <= $program["program_length"]; $level++): ?>
        <div
            class="relative flex flex-col justify-between p-6 transition-all duration-200 bg-white border border-gray-200 shadow-sm rounded-xl hover:-translate-y-1 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">

            <!-- Accent Bar -->
            <span class="absolute top-0 left-0 w-full h-1 bg-blue-500 rounded-t-xl"></span>

            <!-- Card Header -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 text-blue-600 rounded-lg bg-blue-50 dark:bg-blue-500/10 dark:text-blue-400">
                        <i class="text-xl fa-solid fa-graduation-cap"></i>
                    </div>

                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        Level <?= $level * 100 ?>
                    </span>
                </div>

                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    Year <?= $level ?>
                </h3>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Manage academic and administrative settings for this year level.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 space-y-2">
                <!-- Manage Courses -->
                <a href="<?= route("program.manage", ["program_id" => $program_id, "form_level" => $level]) ?>"
                   class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                    <i class="fa-solid fa-book-open"></i>
                    Manage Courses
                </a>

                <!-- Manage Classes -->
                <a href="javascript:void(0)"
                   class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white transition rounded-lg bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                    <i class="fa-solid fa-users"></i>
                    Manage Classes
                </a>

                <!-- Assessments / Exams -->
                <a href="javascript:void(0)"
                   class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-purple-600 rounded-lg hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600">
                    <i class="fa-solid fa-file-lines"></i>
                    Assessments
                </a>

                <!-- Settings -->
                <a href="javascript:void(0)"
                   class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    <i class="fa-solid fa-gear"></i>
                    Year Settings
                </a>
            </div>
        </div>
    <?php endfor; ?>
</div>

</div>

<?php else: ?>

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
        <?= td("__PROGRAM_NAME__") ?>

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
    <?= td_empty("No courses found", 4) ?>
</template>


<div class="max-w-96 w-72">
    <?= button("button", "Add New Course", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-course-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(); ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Course Name") ?>
                    <?= th("Course Code") ?>
                    <?= th("Program Name") ?>
                    <?= th("Actions") ?>
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

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("Add New Course", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="course-form" method="POST">
                <div class="grid gap-4 lg:gap-6">
                    <?= input("hidden", name: "course_id") ?>

                    <?= input("text", "Course Name", "name", required: true, attributes: placeholder("Name of the course")); ?>

                    <?= input_h("text", "Course Code", "code", sub_text:"Keep blank if you want the system to manually define the code", attributes: placeholder("Enter course code")); ?>

                    <?= input("hidden", name: "program_id", value: $program_id) ?>
                    <?= input("text", "Course Program", value: $program["name"], attributes: attribute("readonly")) ?>

                    <?= 
                        select_h(
                            "course_semester", "Course Semester", $course_semesters, 
                            "This will be used to automate course registrations for students",
                            "Select Course Semester"
                        )
                    ?>

                    <?= 
                        select_h(text: "Year Level", options: $year_levels, value: $form_level,
                        sub_text: "This will be used to automate course registrations for students",
                        nullable: "Select Year Level", attributes: attribute("disabled")
                        )
                    ?>
                    <?= input("hidden", name: "year_level", value: $form_level) ?>
                    
                </div>

                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Course", "submit", "create_course", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("courses", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this course. Proceed to delete this course?") ?>
    </div>
<?= modal_end() ?>
<?php endif; ?>

<?php
if(isset($form_level)){
    // Set up the pagination script call
    $pagination_script = pagination_script(
        'admin/ajax/school.php',    // Target AJAX file (you need to create this)
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
        ["submit" => "fetch_courses", "year_level" => $form_level, "program_id" => $program_id] // The action to trigger
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
}

?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');