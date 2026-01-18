<?php
require_once relative_path("includes/components.php");

$title = 'Student Promotion'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<!-- Student Promotion Form -->
<div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-graduation-cap mr-2"></i>Bulk Student Promotion
    </h3>
    
    <?= information_bar(
        "Promote students to the next academic level. This action will update all selected students' current year.",
        "info",
        false,
        attribute("class", "mb-4")
    ) ?>

    <form action="<?= url('admin/submit.php') ?>" method="POST" id="promotion-form">
        <?= input("hidden", "", "request_type", "student_promotion") ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- From Level -->
            <?php 
                $levels = [
                    ["id" => 100, "text" => "Level 100"],
                    ["id" => 200, "text" => "Level 200"],
                    ["id" => 300, "text" => "Level 300"],
                    ["id" => 400, "text" => "Level 400"]
                ];
            ?>
            <?= select("from_level", "From Level", $levels, "Select Level", required: true) ?>
            
            <!-- To Level -->
            <?= select("to_level", "To Level", $levels, "Select Level", required: true) ?>
            
            <!-- Program Filter (Optional) -->
            <?php $programs = programs(); ?>
            <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name")) ?>
            
            <!-- Session/Term Filter (Optional) -->
            <?php 
                $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "session_name", true);
                $session_options = [["id" => "", "text" => "All Sessions"]];
                if(is_array($sessions) && !empty($sessions)) {
                    foreach($sessions as $session) {
                        $session_options[] = ["id" => $session['id'], "text" => $session['session_name']];
                    }
                }
            ?>
            <?= select("session_id", "Academic Session (Optional)", $session_options, "All Sessions") ?>
        </div>
        
        <!-- Filter Criteria Info -->
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-info-circle mr-2"></i>
                Students matching the selected criteria will be promoted from the "From Level" to the "To Level".
            </p>
        </div>
        
        <div class="mt-6">
            <?= button("submit", "Preview Promotion", "submit", "preview_promotion", "purple") ?>
        </div>
    </form>
</div>

<!-- Preview Section (Shown after preview) -->
<div id="promotion-preview" class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hidden">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-list mr-2"></i>Promotion Preview
    </h3>
    
    <div id="preview-stats" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Stats will be populated via AJAX -->
    </div>
    
    <div class="mb-4">
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Index Number") ?>
                <?= th("Name") ?>
                <?= th("Current Level") ?>
                <?= th("New Level") ?>
                <?= th("Program") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Select criteria and click 'Preview Promotion' to see students.", 5) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
    
    <div class="flex gap-4">
        <?= button("button", "Cancel", "cancel_promotion", "", "gray", attribute("id", "cancel-promotion")) ?>
        <?= button("submit", "Confirm Promotion", "submit", "confirm_promotion", "green", attribute("id", "confirm-promotion")) ?>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Handle preview promotion
    $("#promotion-form").on("submit", function(e) {
        if($("input[name='submit']").val() === "preview_promotion") {
            e.preventDefault();
            
            $.ajax({
                url: relative_path("admin/ajax/student.php"),
                type: "POST",
                data: $(this).serialize() + "&submit=preview_promotion",
                dataType: "json",
                success: function(response) {
                    if(response.status) {
                        // Show preview section
                        $("#promotion-preview").removeClass("hidden");
                        // Populate preview data (implementation needed)
                        // Load preview via pagination script if needed
                    } else {
                        alert(response.message || "Error loading preview");
                    }
                },
                error: function() {
                    alert("Error occurred while previewing promotion");
                }
            });
        }
    });
    
    // Cancel promotion preview
    $("#cancel-promotion").click(function() {
        $("#promotion-preview").addClass("hidden");
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
