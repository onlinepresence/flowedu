<?php
require_once relative_path("includes/components.php");

$title = 'Staff Assignments'; // Set the page title
$page_title = 'Assign Admin Staff to Departments/Offices';

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Assign Staff to Department/Office -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-user-plus mr-2"></i>Assign Staff to Department/Office
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="assignment-form">
            <?= input("hidden", "", "submit", "assign_staff") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Staff Selection -->
                <?= input("text", "Staff Name/ID", "staff_search", "", true, attribute("placeholder", "Search staff by name or ID")) ?>
                
                <?php 
                    $departments = fetchData("id, name", "departments", [], 0);
                    $dept_options = [["id" => "", "text" => "Select Department"]];
                    if(is_array($departments) && !empty($departments)) {
                        foreach($departments as $dept) {
                            $dept_options[] = ["id" => $dept['id'], "text" => $dept['name']];
                        }
                    }
                ?>
                <?= select("department_id", "Department", $dept_options, "Select Department", required: true, keys: select_keys("id", "text")) ?>
                
                <?php 
                    $offices = [
                        ["id" => "", "text" => "Select Office"],
                        ["id" => "registrar_office", "text" => "Registrar's Office"],
                        ["id" => "bursary", "text" => "Bursary"],
                        ["id" => "library", "text" => "Library"],
                        ["id" => "accounts", "text" => "Accounts Office"],
                        ["id" => "admissions", "text" => "Admissions Office"],
                        ["id" => "student_affairs", "text" => "Student Affairs"],
                        ["id" => "security", "text" => "Security Office"],
                        ["id" => "maintenance", "text" => "Maintenance Office"]
                    ];
                ?>
                <?= select("office", "Office/Unit", $offices, "Select Office", required: true) ?>
                
                <?php 
                    $positions = [
                        ["id" => "head", "text" => "Head"],
                        ["id" => "deputy", "text" => "Deputy"],
                        ["id" => "officer", "text" => "Officer"],
                        ["id" => "assistant", "text" => "Assistant"],
                        ["id" => "clerk", "text" => "Clerk"]
                    ];
                ?>
                <?= select("position_title", "Position/Title", $positions, "Select Position", required: true) ?>
                
                <?= input("date", "Assignment Date", "assignment_date", date('Y-m-d'), true) ?>
            </div>
            
            <?= information_bar(
                "Assigning staff to departments/offices determines their administrative responsibilities and access levels.",
                "info",
                false,
                attribute("class", "mt-4")
            ) ?>
            
            <div class="mt-6">
                <?= button("submit", "Assign Staff", "submit", "assign_staff", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Current Assignments -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Current Staff Assignments
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?= input("text", "Search Staff", "search", "", false, array_merge(
                attribute("id", "search-staff"),
                attribute("placeholder", "Staff name or ID"),
                data_attr("filter", "search")
            )) ?>
            
            <?php 
                $departments = fetchData("id, name", "departments", [], 0);
                $dept_options = [["id" => "", "text" => "All Departments"]];
                if(is_array($departments) && !empty($departments)) {
                    foreach($departments as $dept) {
                        $dept_options[] = ["id" => $dept['id'], "text" => $dept['name']];
                    }
                }
            ?>
            <?= select("filter_department", "Department", $dept_options, "All Departments", keys: select_keys("id", "text"), attributes: array_merge(
                attribute("id", "filter-department"),
                data_attr("filter", "department")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Staff") ?>
                <?= th("Department") ?>
                <?= th("Office/Unit") ?>
                <?= th("Position") ?>
                <?= th("Assignment Date") ?>
                <?= th("Status") ?>
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
    // Implementation needed
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
