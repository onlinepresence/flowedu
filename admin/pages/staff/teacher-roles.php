<?php
require_once relative_path("includes/components.php");

$title = 'Teacher Roles'; // Set the page title
$page_title = 'Manage Teacher Roles';

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Assign Roles to Teachers -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-user-tag mr-2"></i>Assign Roles to Teachers
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="role-assignment-form">
            <?= input("hidden", "", "submit", "assign_teacher_role") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= input("text", "Teacher Name/ID", "teacher_search", "", true, attribute("placeholder", "Search teacher by name or ID")) ?>
                
                <?php 
                    // Teacher-specific roles
                    $roles = [
                        ["id" => "class_teacher", "text" => "Class Teacher"],
                        ["id" => "head_of_department", "text" => "Head of Department"],
                        ["id" => "dean", "text" => "Dean"],
                        ["id" => "examinations_officer", "text" => "Examinations Officer"],
                        ["id" => "level_coordinator", "text" => "Level Coordinator"],
                        ["id" => "program_coordinator", "text" => "Program Coordinator"],
                        ["id" => "course_coordinator", "text" => "Course Coordinator"],
                        ["id" => "research_supervisor", "text" => "Research Supervisor"]
                    ];
                ?>
                <?= select("role", "Role", $roles, "Select Role", required: true) ?>
            </div>
            
            <?= textarea("description", "Description (Optional)", "", false, attribute("rows", "3"), attribute("placeholder", "Additional notes about this role assignment")) ?>
            
            <?php 
                // Scope for the role (which program, level, course, etc.)
                $programs = programs();
            ?>
            <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name"), attributes: attribute("id", "role-program")) ?>
            
            <div class="mt-6">
                <?= button("submit", "Assign Role", "submit", "assign_teacher_role", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Current Role Assignments -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Current Teacher Role Assignments
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= input("text", "Search Teacher", "search", "", false, array_merge(
                attribute("id", "search-teacher"),
                attribute("placeholder", "Teacher name or ID"),
                data_attr("filter", "search")
            )) ?>
            
            <?php 
                $roles_filter = [
                    ["id" => "", "text" => "All Roles"],
                    ["id" => "class_teacher", "text" => "Class Teacher"],
                    ["id" => "head_of_department", "text" => "Head of Department"],
                    ["id" => "dean", "text" => "Dean"],
                    ["id" => "examinations_officer", "text" => "Examinations Officer"],
                    ["id" => "level_coordinator", "text" => "Level Coordinator"],
                    ["id" => "program_coordinator", "text" => "Program Coordinator"]
                ];
            ?>
            <?= select("filter_role", "Role", $roles_filter, "All Roles", attributes: array_merge(
                attribute("id", "filter-role"),
                data_attr("filter", "role")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Teacher") ?>
                <?= th("Role") ?>
                <?= th("Program/Scope") ?>
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
