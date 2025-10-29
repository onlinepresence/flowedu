<?php
require_once relative_path("includes/components.php");

$title = 'Students Data';

// Start output buffering to capture the content
ob_start();

// Template for table rows
$row_template = <<<HTML
<template id="student-row-template">
    <tr class="text-gray-700 dark:text-gray-400">
        <td class="px-4 py-3">
            <div class="flex items-center text-sm">
                <div class="relative hidden w-8 h-8 mr-3 rounded-full md:block">
                    <img class="object-cover w-full h-full rounded-full" src="__PROFILE_PIC__" alt="">
                    <div class="absolute inset-0 rounded-full shadow-inner" aria-hidden="true"></div>
                </div>
                <div>
                    <p class="font-semibold">__INDEX_NUMBER__</p>
                </div>
            </div>
        </td>
        <td class="px-4 py-3 text-sm">__NAME__</td>
        <td class="px-4 py-3 text-sm">__GENDER__</td>
        <td class="px-4 py-3 text-sm">__FORM_LEVEL__</td>
        <td class="px-4 py-3 text-sm">__PROGRAM__</td>
        <td class="px-4 py-3 text-sm">
            <div class="flex items-center space-x-4 text-sm">
                <button class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </td>
    </tr>
</template>

<template id="empty-row-template">
    <tr class="text-gray-700 dark:text-gray-400">
        <td colspan="5" class="px-4 py-3 text-sm text-center">No students found</td>
    </tr>
</template>
HTML;
?>

<!-- filters -->
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
        <?= button("button", "Download") ?>
    </div>
</div>

<!-- results to be displayed in table -->
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
                <?= td_empty("Loading students...", 5) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
    
    <!-- Pagination -->
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> students
        </p>
        <span class="col-span-2"></span>
        <!-- Pagination -->
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

<?= $row_template ?>

<?php
    $pagination_script = pagination_script(
        'admin/pages/students/submit.php', 'student-row-template', 'students', 
        [
            "PROFILE_PIC" => "profile_pic", "INDEX_NUMBER" => "index_number", "NAME" => "fullname",
            "GENDER" => "gender", "PROGRAM" => "program_name", "FORM_LEVEL" => "current_year"
        ],
        ["submit" => "fetch_students"], ["profile_pic"]);
        
    $scripts = <<<HTML
    <script>
        $(document).ready(function(){
            $pagination_script

            // Handle filter changes
            $('#level, #faculty, #department, #program').on('change', function() {
                loadPaginatedData(1);
            });

            // Handle search button click
            $('#search-btn').on('click', function() {
                loadPaginatedData(1);
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
