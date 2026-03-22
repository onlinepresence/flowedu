<?php
require_once relative_path("includes/components.php");

$title = 'Upload Results'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Upload Results -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-upload mr-2"></i>Bulk Upload Results
        </h3>
        
        <?= information_bar(
            "Upload results from an Excel file. Download the template to ensure correct format.",
            "info",
            false,
            attribute("class", "mb-4")
        ) ?>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" enctype="multipart/form-data" id="upload-form">
            <?= input("hidden", "", "request_type", "upload_results") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                
                <?php $programs = programs(); ?>
                <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name")) ?>
                
                <?php 
                    $levels = [
                        ["id" => "", "text" => "All Levels"],
                        ["id" => 100, "text" => "Level 100"],
                        ["id" => 200, "text" => "Level 200"],
                        ["id" => 300, "text" => "Level 300"],
                        ["id" => 400, "text" => "Level 400"]
                    ];
                ?>
                <?= select("level", "Level (Optional)", $levels, "All Levels") ?>
            </div>
            
            <div class="mt-6">
                <?= input_h("file", "Results File (Excel)", "results_file", sub_text: "Accepted formats: .xlsx, .xls", required: true, attributes: array_merge(
                    attribute("accept", ".xlsx,.xls"),
                    data_attr("file-upload", "results")
                )) ?>
            </div>
            
            <div class="mt-6 flex gap-4">
                <?= button("button", "Download Template", attributes: array_merge(
                    attribute("id", "download-template-btn"),
                    attribute("type", "button"),
                    attribute("class", "max-w-xs")
                )) ?>
                <?= button("submit", "Upload Results", "submit", "upload_results", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Upload History -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-history mr-2"></i>Upload History
        </h3>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Upload Date") ?>
                <?= th("Session") ?>
                <?= th("Program") ?>
                <?= th("File Name") ?>
                <?= th("Records") ?>
                <?= th("Status") ?>
                <?= th("Uploaded By") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("No upload history available.", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Download template
    $("#download-template-btn").click(function() {
        window.location.href = relative_path("admin/ajax/grading.php?submit=download_template");
    });
    
    // File upload preview/validation
    $("input[type='file']").on("change", function() {
        const file = this.files[0];
        if(file) {
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();
            
            if(!['xlsx', 'xls'].includes(fileExtension)) {
                alert("Please select a valid Excel file (.xlsx or .xls)");
                $(this).val('');
            }
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
