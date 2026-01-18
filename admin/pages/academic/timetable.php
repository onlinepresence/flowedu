<?php
require_once relative_path("includes/components.php");

$title = 'Timetable Management'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Timetable Actions -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-calendar-alt mr-2"></i>Create/Manage Timetable
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="timetable-form">
            <?= input("hidden", "", "request_type", "create_timetable") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                
                <?php 
                    $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "session_name", true);
                    $session_options = [];
                    if(is_array($sessions) && !empty($sessions)) {
                        foreach($sessions as $session) {
                            $session_options[] = ["id" => $session['id'], "text" => $session['session_name']];
                        }
                    }
                ?>
                <?= select("session_id", "Academic Session", $session_options, "Select Session", required: true) ?>
            </div>
            
            <div class="mt-6">
                <?= button("button", "Load/Create Timetable", attributes: array_merge(
                    attribute("id", "load-timetable-btn"),
                    attribute("type", "button")
                )) ?>
            </div>
        </form>
    </div>

    <!-- Timetable Schedule -->
    <div class="mb-6 hidden" id="timetable-schedule">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-table mr-2"></i>Timetable Schedule
        </h3>
        
        <?= information_bar(
            "Click on a time slot to add or edit a class. Drag to rearrange if needed.",
            "info",
            false,
            attribute("class", "mb-4")
        ) ?>
        
        <div class="grid grid-cols-1 gap-4">
            <?php 
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                foreach($days as $day): 
            ?>
                <div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
                    <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-200">
                        <i class="fas fa-calendar-day mr-2"></i><?= $day ?>
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3" id="timetable-<?= strtolower($day) ?>">
                        <!-- Time slots will be populated via AJAX -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-6 flex gap-4">
            <?= button("button", "Add Class", attributes: array_merge(
                attribute("id", "add-class-btn"),
                attribute("class", "max-w-xs"),
                attribute("type", "button")
            )) ?>
            <?= button("submit", "Save Timetable", "submit", "save_timetable", "green", attribute("id", "save-timetable-btn")) ?>
        </div>
    </div>

    <!-- View Timetables List -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Existing Timetables
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
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
                <?= th("Program") ?>
                <?= th("Level") ?>
                <?= th("Session") ?>
                <?= th("Created") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading timetables...", 5) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<!-- Add/Edit Class Modal -->
<div id="class-modal-content" class="hidden">
    <form action="<?= url('admin/submit.php') ?>" method="POST" id="class-form">
        <?= input("hidden", "", "request_type", "add_timetable_class") ?>
        <?= input("hidden", "", "timetable_id", "", false, attribute("id", "timetable-id")) ?>
        
        <div class="grid grid-cols-1 gap-6">
            <?php 
                $days = [
                    ["id" => "monday", "text" => "Monday"],
                    ["id" => "tuesday", "text" => "Tuesday"],
                    ["id" => "wednesday", "text" => "Wednesday"],
                    ["id" => "thursday", "text" => "Thursday"],
                    ["id" => "friday", "text" => "Friday"]
                ];
            ?>
            <?= select("day", "Day", $days, "Select Day", required: true) ?>
            
            <?= input("time", "Start Time", "start_time", "", true) ?>
            <?= input("time", "End Time", "end_time", "", true) ?>
            
            <!-- Course selection (would need to fetch courses for the selected program/level) -->
            <?= input("text", "Course Code", "course_code", "", true, attribute("placeholder", "e.g., CS 101")) ?>
            <?= input("text", "Course Name", "course_name", "", true) ?>
            
            <?= input("text", "Venue/Location", "venue", "", true, attribute("placeholder", "e.g., LT 1, Lab 3")) ?>
            
            <!-- Teacher selection (would need to fetch teachers) -->
            <?= input("text", "Lecturer/Teacher", "lecturer", "", true) ?>
        </div>
        
        <div class="mt-6">
            <?= button("submit", "Save Class", "submit", "add_timetable_class", "purple") ?>
        </div>
    </form>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Helper function to display alert box
    function showAlert(message, type = 'danger') {
        if (typeof alert_box === "function") {
            alert_box(message, type); // custom alert function if defined elsewhere
        } else {
            alert(message); // fallback for now
        }
    }

    // Load timetable
    $("#load-timetable-btn").click(function() {
        const programId = $("#program_id").val();
        const level = $("#level").val();
        const sessionId = $("#session_id").val();
        
        if(!programId || !level || !sessionId) {
            showAlert("Please select program, level, and session", "warning");
            return;
        }
        
        $.ajax({
            url: relative_path("admin/ajax/academic.php"),
            type: "POST",
            data: {
                submit: "load_timetable",
                program_id: programId,
                level: level,
                session_id: sessionId
            },
            dataType: "json",
            success: function(response) {
                if(response.status) {
                    $("#timetable-schedule").removeClass("hidden");
                    // Populate timetable schedule
                } else {
                    let msg = response.errors && response.errors.system_error ? response.errors.system_error : "Failed to load timetable";
                    showAlert(msg, "danger");
                }
            },
            error: function(xhr) {
                showAlert("A system error occurred while loading the timetable. Please try again.", "danger");
            }
        });
    });
    
    // Add class button
    $("#add-class-btn").click(function() {
        openModal("class-modal");
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
