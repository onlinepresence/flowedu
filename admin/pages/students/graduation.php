<?php
require_once relative_path("includes/components.php");

$title = 'Graduation Management'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Graduation Actions -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-user-graduate mr-2"></i>Process Graduation
            </h3>
            
            <form action="<?= url('admin/submit.php') ?>" method="POST">
                <?= input("hidden", "", "request_type", "process_graduation") ?>
                
                <div class="grid grid-cols-1 gap-4">
                    <?php 
                        $levels = [
                            ["id" => 400, "text" => "Level 400 (Final Year)"]
                        ];
                    ?>
                    <?= select("level", "Student Level", $levels, "Select Level", required: true) ?>
                    
                    <?php $programs = programs(); ?>
                    <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name")) ?>
                    
                    <?php 
                        $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "session_name", true);
                        $session_options = [["id" => "", "text" => "Current Session"]];
                        if(is_array($sessions) && !empty($sessions)) {
                            foreach($sessions as $session) {
                                $session_options[] = ["id" => $session['id'], "text" => $session['session_name']];
                            }
                        }
                    ?>
                    <?= select("session_id", "Academic Session", $session_options, "Current Session", required: true) ?>
                    
                    <?= input("date", "Graduation Date", "graduation_date", "", true) ?>
                </div>
                
                <?= information_bar(
                    "This will mark eligible final year students as graduated. Ensure all requirements are met.",
                    "warning",
                    false,
                    attribute("class", "mt-4")
                ) ?>
                
                <div class="mt-4">
                    <?= button("submit", "Process Graduation", "submit", "process_graduation", "purple") ?>
                </div>
            </form>
        </div>
        
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-chart-bar mr-2"></i>Graduation Statistics
            </h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Graduated</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="total-graduated">-</p>
                </div>
                <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">This Year</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" id="this-year-graduated">-</p>
                </div>
            </div>
            
            <div class="mt-4">
                <button 
                    onclick="loadGraduationStats()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                >
                    <i class="fas fa-sync-alt mr-2"></i>Refresh Stats
                </button>
            </div>
        </div>
    </div>

    <!-- Graduated Students List -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Graduated Students
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "filter-program"),
                data_attr("filter", "program")
            )) ?>
            
            <?= input("date", "From Date", "filter_from_date", "", false, array_merge(
                attribute("id", "filter-from-date"),
                data_attr("filter", "from_date")
            )) ?>
            
            <?= input("date", "To Date", "filter_to_date", "", false, array_merge(
                attribute("id", "filter-to-date"),
                data_attr("filter", "to_date")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Index Number") ?>
                <?= th("Name") ?>
                <?= th("Program") ?>
                <?= th("Graduation Date") ?>
                <?= th("Status") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading graduated students...", 6) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
        
        <!-- Pagination will be handled via AJAX -->
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    loadGraduationStats();
    
    // Load graduated students via AJAX
    function loadGraduatedStudents() {
        // Implementation for loading graduated students list
        // Use pagination script similar to students/index.php
    }
    
    function loadGraduationStats() {
        $.ajax({
            url: relative_path("admin/ajax/student.php"),
            type: "POST",
            data: { submit: "get_graduation_stats" },
            dataType: "json",
            success: function(response) {
                if(response.status) {
                    $("#total-graduated").text(response.data.total || 0);
                    $("#this-year-graduated").text(response.data.this_year || 0);
                }
            }
        });
    }
    
    // Filter handlers
    $("#filter-program, #filter-from-date, #filter-to-date").on("change", function() {
        loadGraduatedStudents();
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
