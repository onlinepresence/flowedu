<?php
require_once relative_path("includes/components.php");

$title = 'Enter Results'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Select Course/Session -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-edit mr-2"></i>Enter Student Results
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="GET" id="select-course-form">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <?php $programs = programs(); ?>
                <?= select("program_id", "Program", $programs, "Select Program", required: true, keys: select_keys("id", "name"), attributes: array_merge(
                    attribute("id", "program"),
                    data_attr("filter", "program")
                )) ?>
                
                <?php 
                    $levels = [
                        ["id" => 100, "text" => "Level 100"],
                        ["id" => 200, "text" => "Level 200"],
                        ["id" => 300, "text" => "Level 300"],
                        ["id" => 400, "text" => "Level 400"]
                    ];
                ?>
                <?= select("level", "Level", $levels, "Select Level", required: true, attributes: array_merge(
                    attribute("id", "level"),
                    data_attr("filter", "level")
                )) ?>
                
                <!-- Course selection would be populated via AJAX based on program/level -->
                <?= select("course_id", "Course", [["id" => "", "text" => "Select Course"]], "Select Course", required: true, attributes: array_merge(
                    attribute("id", "course"),
                    data_attr("filter", "course")
                )) ?>
                
                <?php 
                    $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "name", true);
                    $session_options = [];
                    if(is_array($sessions) && !empty($sessions)) {
                        foreach($sessions as $session) {
                            $session_options[] = ["id" => $session['id'], "text" => $session['name']];
                        }
                    }
                ?>
                <?= select("session_id", "Session", $session_options, "Select Session", required: true, attributes: array_merge(
                    attribute("id", "session"),
                    data_attr("filter", "session")
                )) ?>
            </div>
            
            <div class="mt-6">
                <?= button("button", "Load Students", attributes: array_merge(
                    attribute("id", "load-students-btn"),
                    attribute("type", "button")
                )) ?>
            </div>
        </form>
    </div>

    <!-- Results Entry Form -->
    <div class="mb-6 hidden" id="results-entry">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-clipboard-list mr-2"></i>Enter Results
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="results-form">
            <?= input("hidden", "", "request_type", "enter_results") ?>
            <?= input("hidden", "", "course_id", "", false, attribute("id", "form-course-id")) ?>
            <?= input("hidden", "", "session_id", "", false, attribute("id", "form-session-id")) ?>
            
            <?= table_start() ?>
                <?= thead_start() ?>
                    <?= th("Index Number") ?>
                    <?= th("Name") ?>
                    <?= th("Score (%)") ?>
                    <?= th("Grade") ?>
                    <?= th("Grade Points") ?>
                <?= thead_end() ?>
                <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                    <?= td_empty("Select course and session, then click 'Load Students' to begin entering results.", 5) ?>
                <?= tbody_end() ?>
            <?= table_end() ?>
            
            <div class="mt-6">
                <?= button("submit", "Save Results", "submit", "enter_results", "green") ?>
                <?= button("button", "Save as Draft", "draft", "", "gray", attribute("id", "save-draft-btn")) ?>
            </div>
        </form>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Load students when course is selected
    $("#load-students-btn").click(function() {
        const programId = $("#program").val();
        const level = $("#level").val();
        const courseId = $("#course").val();
        const sessionId = $("#session").val();
        
        if(!programId || !level || !courseId || !sessionId) {
            alert("Please select all fields");
            return;
        }
        
        $.ajax({
            url: relative_path("admin/ajax/grading.php"),
            type: "POST",
            data: {
                submit: "load_students_for_results",
                program_id: programId,
                level: level,
                course_id: courseId,
                session_id: sessionId
            },
            dataType: "json",
            success: function(response) {
                if(response.status && response.data) {
                    $("#results-entry").removeClass("hidden");
                    $("#form-course-id").val(courseId);
                    $("#form-session-id").val(sessionId);
                    // Populate table with students and result entry fields
                    // Implementation needed
                } else {
                    alert(response.message || "No students found for this course");
                }
            }
        });
    });
    
    // Auto-calculate grade based on score
    $(document).on("input", ".score-input", function() {
        const score = parseFloat($(this).val());
        const row = $(this).closest("tr");
        
        // Calculate grade and grade points based on score
        // Implementation needed - would use grade points configuration
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
?>
