<?php
require_once relative_path("includes/components.php");

$title = 'Staff Roles'; // Set the page title
$page_title = 'Manage Admin Staff Roles';

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Assign Roles to Staff -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-user-tag mr-2"></i>Assign Roles to Admin Staff
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="role-assignment-form">
            <?= input("hidden", "", "submit", "assign_staff_role") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= input("text", "Staff Name/ID", "staff_search", "", true, attribute("placeholder", "Search staff by name or ID")) ?>
                
                <?php 
                    // Admin staff-specific roles
                    $roles = [
                        ["id" => "registrar", "text" => "Registrar"],
                        ["id" => "deputy_registrar", "text" => "Deputy Registrar"],
                        ["id" => "bursar", "text" => "Bursar"],
                        ["id" => "deputy_bursar", "text" => "Deputy Bursar"],
                        ["id" => "librarian", "text" => "Librarian"],
                        ["id" => "accountant", "text" => "Accountant"],
                        ["id" => "admissions_officer", "text" => "Admissions Officer"],
                        ["id" => "student_affairs_officer", "text" => "Student Affairs Officer"],
                        ["id" => "examinations_officer", "text" => "Examinations Officer"],
                        ["id" => "hr_officer", "text" => "HR Officer"],
                        ["id" => "it_officer", "text" => "IT Officer"]
                    ];
                ?>
                <?= select("role", "Role", $roles, "Select Role", required: true) ?>
            </div>
            
            <?php 
                // Department assignment for staff roles
                $departments = fetchData("id, name", "departments", [], 0);
                $dept_options = [["id" => "", "text" => "Select Department (Optional)"]];
                if(is_array($departments) && !empty($departments)) {
                    foreach($departments as $dept) {
                        $dept_options[] = ["id" => $dept['id'], "text" => $dept['name']];
                    }
                }
            ?>
            <?= select("department_id", "Department", $dept_options, "Select Department", keys: select_keys("id", "text"), attributes: attribute("id", "role-department")) ?>
            
            <?= textarea("description", "Description (Optional)", "", false, attribute("rows", "3"), attribute("placeholder", "Additional notes about this role assignment")) ?>
            
            <div class="mt-6">
                <?= button("submit", "Assign Role", "submit", "assign_staff_role", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Current Role Assignments -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Current Staff Role Assignments
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= input("text", "Search Staff", "search", "", false, array_merge(
                attribute("id", "search-staff"),
                attribute("placeholder", "Staff name or ID"),
                data_attr("filter", "search")
            )) ?>
            
            <?php 
                $roles_filter = [
                    ["id" => "", "text" => "All Roles"],
                    ["id" => "registrar", "text" => "Registrar"],
                    ["id" => "bursar", "text" => "Bursar"],
                    ["id" => "librarian", "text" => "Librarian"],
                    ["id" => "accountant", "text" => "Accountant"],
                    ["id" => "admissions_officer", "text" => "Admissions Officer"]
                ];
            ?>
            <?= select("filter_role", "Role", $roles_filter, "All Roles", attributes: array_merge(
                attribute("id", "filter-role"),
                data_attr("filter", "role")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Staff") ?>
                <?= th("Role") ?>
                <?= th("Department") ?>
                <?= th("Description") ?>
                <?= th("Assigned Date") ?>
                <?= th("Status") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading role assignments...", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
