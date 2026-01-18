<?php
require_once relative_path("includes/components.php");

$title = 'Student Medical Information'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Search/Filter Section -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-search mr-2"></i>Search Student
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?= input("text", "Index Number / Name", "search", "", false, array_merge(
                attribute("id", "search-medical"),
                attribute("placeholder", "Enter index number or name"),
                data_attr("filter", "search")
            )) ?>
            
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "filter-program"),
                data_attr("filter", "program")
            )) ?>
            
            <div class="flex items-end">
                <?= button("button", "Search", attributes: array_merge(
                    attribute("id", "search-btn"),
                    attribute("class", "w-full")
                )) ?>
            </div>
        </div>
    </div>

    <!-- Student Medical Records -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-file-medical mr-2"></i>Medical Records
        </h3>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Student") ?>
                <?= th("Index Number") ?>
                <?= th("Blood Type") ?>
                <?= th("Allergies") ?>
                <?= th("Insurance Number") ?>
                <?= th("Emergency Contact") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Search for a student to view medical information.", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>

    <!-- Add/Edit Medical Record Modal Content -->
    <div id="medical-modal-content" class="hidden">
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="medical-form">
            <?= input("hidden", "", "request_type", "update_medical") ?>
            <?= input("hidden", "", "user_id", "", false, attribute("id", "medical-user-id")) ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= input("text", "Blood Type", "blood_type", "", false, attribute("placeholder", "e.g., O+")) ?>
                
                <?= textarea("allergies", "Known Allergies", "", false, attribute("placeholder", "List any known allergies")) ?>
                
                <?= input("text", "Insurance Number", "insurance_number", "", false, attribute("placeholder", "Medical insurance number")) ?>
                
                <?= textarea("chronic_conditions", "Chronic Conditions", "", false, attribute("placeholder", "Any chronic medical conditions")) ?>
            </div>
            
            <h4 class="mt-6 mb-4 text-md font-semibold text-gray-700 dark:text-gray-200">Emergency Contact</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?= input("text", "Contact Name", "emergency_contact_name", "", true) ?>
                <?= input("text", "Relationship", "emergency_relationship", "", true, attribute("placeholder", "e.g., Parent, Guardian")) ?>
                <?= input("tel", "Phone Number", "emergency_phone", "", true) ?>
            </div>
            
            <div class="mt-6">
                <?= button("submit", "Save Medical Information", "submit", "update_medical", "purple") ?>
            </div>
        </form>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Search functionality
    $("#search-btn").click(function() {
        const search = $("#search-medical").val();
        const program = $("#filter-program").val();
        
        $.ajax({
            url: relative_path("admin/ajax/student.php"),
            type: "POST",
            data: {
                submit: "search_medical_records",
                search: search,
                program_id: program
            },
            dataType: "json",
            success: function(response) {
                if(response.status && response.data) {
                    // Populate table with medical records
                    // Implementation needed
                } else {
                    alert(response.message || "No medical records found");
                }
            }
        });
    });
    
    // Handle edit medical record
    $(document).on("click", ".edit-medical", function() {
        const userId = $(this).data("id");
        // Load medical record data and populate form
        // Show modal with form
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
