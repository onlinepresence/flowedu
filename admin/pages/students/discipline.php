<?php
require_once relative_path("includes/components.php");

$title = 'Student Disciplinary Records'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Add Disciplinary Record -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-plus-circle mr-2"></i>Add Disciplinary Record
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="discipline-form">
            <?= input("hidden", "", "request_type", "add_disciplinary_record") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Student Selection -->
                <?= input("text", "Student Index Number", "student_index", "", true, attribute("placeholder", "Enter student index number")) ?>
                
                <!-- Incident Details -->
                <?= textarea("incident", "Incident Description", "", true, attribute("rows", "4"), attribute("placeholder", "Describe the incident")) ?>
                
                <!-- Violation Type -->
                <?php 
                    $violation_types = [
                        ["id" => "academic", "text" => "Academic Violation"],
                        ["id" => "behavioral", "text" => "Behavioral Violation"],
                        ["id" => "conduct", "text" => "Code of Conduct Violation"],
                        ["id" => "other", "text" => "Other"]
                    ];
                ?>
                <?= select("violation_type", "Violation Type", $violation_types, "Select Type", required: true) ?>
                
                <!-- Severity -->
                <?php 
                    $severity_levels = [
                        ["id" => "minor", "text" => "Minor"],
                        ["id" => "moderate", "text" => "Moderate"],
                        ["id" => "major", "text" => "Major"]
                    ];
                ?>
                <?= select("severity", "Severity Level", $severity_levels, "Select Severity", required: true) ?>
                
                <!-- Incident Date -->
                <?= input("date", "Incident Date", "incident_date", "", true) ?>
                
                <!-- Action Taken -->
                <?= textarea("action_taken", "Action Taken", "", true, attribute("rows", "3"), attribute("placeholder", "e.g., Warning issued, Counseling session, Suspension")) ?>
            </div>
            
            <div class="mt-6">
                <?= button("submit", "Add Disciplinary Record", "submit", "add_disciplinary_record", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Disciplinary Records List -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Disciplinary Records
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= input("text", "Student Search", "search_student", "", false, array_merge(
                attribute("id", "search-student"),
                attribute("placeholder", "Index number or name"),
                data_attr("filter", "search")
            )) ?>
            
            <?php 
                $severity_levels = [
                    ["id" => "", "text" => "All Severities"],
                    ["id" => "minor", "text" => "Minor"],
                    ["id" => "moderate", "text" => "Moderate"],
                    ["id" => "major", "text" => "Major"]
                ];
            ?>
            <?= select("filter_severity", "Severity", $severity_levels, "All Severities", attributes: array_merge(
                attribute("id", "filter-severity"),
                data_attr("filter", "severity")
            )) ?>
            
            <?php 
                $status_options = [
                    ["id" => "", "text" => "All Status"],
                    ["id" => "active", "text" => "Active"],
                    ["id" => "resolved", "text" => "Resolved"],
                    ["id" => "pending", "text" => "Pending"]
                ];
            ?>
            <?= select("filter_status", "Status", $status_options, "All Status", attributes: array_merge(
                attribute("id", "filter-status"),
                data_attr("filter", "status")
            )) ?>
            
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "filter-program"),
                data_attr("filter", "program")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Student") ?>
                <?= th("Index Number") ?>
                <?= th("Incident") ?>
                <?= th("Violation Type") ?>
                <?= th("Severity") ?>
                <?= th("Incident Date") ?>
                <?= th("Status") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading disciplinary records...", 8) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
        
        <!-- Pagination will be handled via AJAX -->
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Load disciplinary records on page load
    loadDisciplinaryRecords();
    
    // Filter handlers
    $("#search-student, #filter-severity, #filter-status, #filter-program").on("change", function() {
        loadDisciplinaryRecords();
    });
    
    function loadDisciplinaryRecords() {
        $.ajax({
            url: relative_path("admin/ajax/student.php"),
            type: "POST",
            data: {
                submit: "fetch_disciplinary_records",
                search: $("#search-student").val(),
                severity: $("#filter-severity").val(),
                status: $("#filter-status").val(),
                program_id: $("#filter-program").val()
            },
            dataType: "json",
            success: function(response) {
                if(response.status && response.data) {
                    // Populate table with disciplinary records
                    // Use pagination script similar to students/index.php
                }
            }
        });
    }
    
    // Handle resolve disciplinary record
    $(document).on("click", ".resolve-record", function() {
        const recordId = $(this).data("id");
        if(confirm("Mark this disciplinary record as resolved?")) {
            $.ajax({
                url: relative_path("admin/ajax/student.php"),
                type: "POST",
                data: {
                    submit: "resolve_disciplinary_record",
                    record_id: recordId
                },
                dataType: "json",
                success: function(response) {
                    if(response.status) {
                        loadDisciplinaryRecords();
                    } else {
                        alert(response.message || "Error resolving record");
                    }
                }
            });
        }
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
