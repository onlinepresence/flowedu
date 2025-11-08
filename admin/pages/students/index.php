<?php
require_once relative_path("includes/components.php");

$title = 'Students Data';

// Start output buffering to capture the content
ob_start();
?>

<!-- ============================================== -->
<!-- 1. TEMPLATES FOR PAGINATION ROWS -->
<!-- ============================================== -->

<template id="student-row-template">
    <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
        <!-- Student Index Number + Profile Picture -->
        <?= td("__INDEX_NUMBER__", "__PROFILE_PIC__") ?>
        <!-- Name -->
        <?= td("__NAME__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <!-- Gender -->
        <?= td("__GENDER__", attributes: attribute("class", "px-4 py-3 text-sm capitalize")) ?>
        <!-- Form Level -->
        <?= td("__FORM_LEVEL__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <!-- Program -->
        <?= td("__PROGRAM__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        
        <!-- Actions -->
        <?= td_actions(
            array_merge(
                create_td_action("fas fa-eye", "View", array_merge(
                    attribute("class", "text-blue-500 cursor-pointer hover:text-blue-600 action-view action-btn"),
                    data_attr("id", "__USER_ID__")
                )),
                create_td_action("fas fa-edit", "Edit", array_merge(
                    attribute("class", "text-yellow-500 cursor-pointer hover:text-yellow-600 action-edit action-btn"),
                    data_attr("id", "__USER_ID__")
                ))
            )
        ) ?>
    <?= tr_end() ?>
</template>

<template id="empty-row-template">
    <?= td_empty("No students found", 6) ?>
</template>

<!-- ============================================== -->
<!-- 2. FILTERS AND DOWNLOAD BUTTON -->
<!-- ============================================== -->

<div>
    <h2 class="mb-2 text-lg font-bold">Filters</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <?= 
            select("level", "Student Level", [
                ["id" => 100, "text" => "Level 100"],
                ["id" => 200, "text" => "Level 200"],
                ["id" => 300, "text" => "Level 300"],
                ["id" => 400, "text" => "Level 400"]
            ], "All Levels", attributes: array_merge(
                attribute("id", "level"), data_attr("filter", "level")
            ))
        ?>
        <?php 
            $faculties = faculties();
            echo select("faculty", "Faculty", $faculties, "All Faculties", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "faculty"), data_attr("filter", "faculty")
            ))
        ?>
        <?php 
            $departments = departments();
            echo select("department", "Department", $departments, "All Departments", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "department"), data_attr("filter", "department")
            ))
        ?>
        <?php 
            $programs = programs();
            echo select("program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "program"), data_attr("filter", "program")
            ))
        ?>
    </div>
    <div class="flex gap-2 mt-4 max-w-52">
        <?= button("button", "Download", attributes: attribute("id", "download")) ?>
    </div>
</div>

<!-- ============================================== -->
<!-- 3. RESULTS TABLE WITH PAGINATION UI -->
<!-- ============================================== -->

<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(attribute("class", "w-full whitespace-no-wrap")) ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th('Student', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Name', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Gender', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Form Level', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Program', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Actions', attribute('class', 'px-4 py-3')) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading students...", 6) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
    
    <!-- Pagination Footer -->
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> students
        </p>
        <span class="col-span-2"></span>
        <!-- Pagination Buttons -->
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
    $pagination_script = pagination_script(
        'admin/ajax/student.php', 
        'student-row-template', 
        'students', 
        [
            "PROFILE_PIC" => "profile_pic", 
            "INDEX_NUMBER" => "index_number", 
            "NAME" => "fullname",
            "GENDER" => "gender", 
            "PROGRAM" => "program_name", 
            "FORM_LEVEL" => "current_year",
            "USER_ID" => "user_id" // Added for actions
        ],
        ["submit" => "fetch_students"], 
        ["profile_pic"]
    );
        
    $scripts = <<<HTML
    <script>
        $(document).ready(function(){
            $pagination_script

            // Handle filter changes (this is key for pagination with filters)
            $('#level, #faculty, #department, #program').on('change', function() {
                // When a filter changes, reset to page 1
                loadPaginatedData(1); 
            });

            // Handle search button click (assuming you add a search input/button)
            $('#search-btn').on('click', function() {
                loadPaginatedData(1);
            });

            $("#download").click(function () {
                const level = $("#level").val();
                const faculty = $("#faculty").val();
                const department = $("#department").val();
                const program = $("#program").val();

                // Note: The download logic still uses a blocking AJAX call, 
                // which is acceptable for file downloads.
                $.ajax({
                    url: relative_path("admin/pages/students/submit.php"),
                    type: "POST",
                    data: {
                        level: level,
                        faculty: faculty,
                        department: department,
                        program: program,
                        submit: "download_students",
                        response_type: "json"
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $("#download").prop("disabled", true).text("Processing...");
                    },
                    success: function (response) {
                        $("#download").prop("disabled", false).text("Download");

                        if (response.status && response.data && response.data.file_url) {
                            // file_url is expected to be provided by the backend, e.g. "files/export.xlsx"
                            const link = document.createElement("a");
                            link.href = response.data.file_url;
                            link.download = response.data.filename || "students.xlsx";
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            // Handle error message
                            const errorMsg = response.errors && response.errors.length
                                ? response.errors.join(" ")
                                : "An unknown error occurred.";
                            // In a real application, replace alert with a modal/message box
                            console.error(errorMsg); 
                        }
                    },
                    error: function (xhr, status, error) {
                        $("#download").prop("disabled", false).text("Download");
                        console.error("Request failed: " + error);
                    }
                });
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