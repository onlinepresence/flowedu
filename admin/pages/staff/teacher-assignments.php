<?php
require_once relative_path("includes/components.php");

$title = 'Teacher Assignments'; // Set the page title
$page_title = 'Assign Teachers to Courses';

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Assign Teacher to Class/Course -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-user-plus mr-2"></i>Assign Teacher to Course
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="assignment-form">
            <?= input("hidden", "", "submit", "assign_teacher") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Teacher Selection -->
                <?= input("text", "Teacher Name/ID", "teacher_search", "", true, attribute("placeholder", "Search teacher by name or ID")) ?>
                
                <?php $programs = programs(); ?>
                <?= select("program_id", "Program", $programs, "Select Program", required: true, keys: select_keys("id", "name")) ?>
                
                <?php 
                    $levels = [
                        ["id" => 100, "text" => "Level 100"],
                        ["id" => 200, "text" => "Level 200"],
                        ["id" => 300, "text" => "Level 300"],
                        ["id" => 400, "text" => "Level 400"]
                    ];
                ?>
                <?= select("level", "Level", $levels, "Select Level", required: true) ?>
                
                <!-- Course selection would be populated via AJAX -->
                <?= select("course_id", "Course/Subject", [["id" => "", "text" => "Select Course"]], "Select Course", required: true) ?>
                
                <?php 
                    $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "name", true);
                    $session_options = [];
                    if(is_array($sessions) && !empty($sessions)) {
                        foreach($sessions as $session) {
                            $session_options[] = ["id" => $session['id'], "text" => $session['name']];
                        }
                    }
                ?>
                <?= select("session_id", "Academic Session", $session_options, "Select Session", required: true) ?>
            </div>
            
            <?= information_bar(
                "Assigning a teacher will allow them to manage classes, take attendance, and enter results for this course.",
                "info",
                false,
                attribute("class", "mt-4")
            ) ?>
            
            <div class="mt-6">
                <?= button("submit", "Assign Teacher", "submit", "assign_teacher", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Current Assignments -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Current Teacher Assignments
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= input("text", "Search Teacher", "search", "", false, array_merge(
                attribute("id", "search-teacher"),
                attribute("placeholder", "Teacher name or ID"),
                data_attr("filter", "search")
            )) ?>
            
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "filter-program"),
                data_attr("filter", "program")
            )) ?>
            
            <?php 
                $levels = [
                    ["id" => "", "text" => "All Levels"],
                    ["id" => 100, "text" => "Level 100"],
                    ["id" => 200, "text" => "Level 200"],
                    ["id" => 300, "text" => "Level 300"],
                    ["id" => 400, "text" => "Level 400"]
                ];
            ?>
            <?= select("filter_level", "Level", $levels, "All Levels", attributes: array_merge(
                attribute("id", "filter-level"),
                data_attr("filter", "level")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Teacher") ?>
                <?= th("Course/Subject") ?>
                <?= th("Program") ?>
                <?= th("Level") ?>
                <?= th("Session") ?>
                <?= th("Assigned Date") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading assignments...", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Load assignments via AJAX
    // Implementation needed - use pagination script similar to students/index.php
});
</script>
HTML;
?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
