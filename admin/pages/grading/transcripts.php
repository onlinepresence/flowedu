<?php
require_once relative_path("includes/components.php");

$title = 'Transcript Management'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Generate Transcript -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-file-alt mr-2"></i>Generate Transcript
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="transcript-form">
            <?= input("hidden", "", "request_type", "generate_transcript") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= input("text", "Student Index Number", "student_index", "", true, attribute("placeholder", "Enter student index number")) ?>
                
                <?php $programs = programs(); ?>
                <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name")) ?>
            </div>
            
            <div class="mt-6 flex gap-4">
                <?= button("submit", "Generate Transcript", "submit", "generate_transcript", "purple") ?>
                <?= button("button", "Bulk Generate", attributes: array_merge(
                    attribute("id", "bulk-generate-btn"),
                    attribute("type", "button")
                )) ?>
            </div>
        </form>
    </div>

    <!-- Transcript Requests/History -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Transcript Requests & History
        </h3>
        
        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?= input("text", "Search Student", "search", "", false, array_merge(
                attribute("id", "search-transcript"),
                attribute("placeholder", "Index number or name"),
                data_attr("filter", "search")
            )) ?>
            
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "filter-program"),
                data_attr("filter", "program")
            )) ?>
            
            <?php 
                $status_options = [
                    ["id" => "", "text" => "All Status"],
                    ["id" => "pending", "text" => "Pending"],
                    ["id" => "generated", "text" => "Generated"],
                    ["id" => "issued", "text" => "Issued"]
                ];
            ?>
            <?= select("filter_status", "Status", $status_options, "All Status", attributes: array_merge(
                attribute("id", "filter-status"),
                data_attr("filter", "status")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Student") ?>
                <?= th("Index Number") ?>
                <?= th("Program") ?>
                <?= th("CGPA") ?>
                <?= th("Request Date") ?>
                <?= th("Status") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading transcript requests...", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    // Bulk generate transcripts
    $("#bulk-generate-btn").click(function() {
        const programId = $("#program_id").val();
        
        if(confirm("Generate transcripts for all students" + (programId ? " in selected program" : "") + "? This may take some time.")) {
            $.ajax({
                url: relative_path("admin/ajax/grading.php"),
                type: "POST",
                data: {
                    submit: "bulk_generate_transcripts",
                    program_id: programId
                },
                dataType: "json",
                success: function(response) {
                    if(response.status) {
                        alert("Transcript generation started. " + response.data.count + " transcripts will be generated.");
                        // Refresh table
                    } else {
                        alert(response.message || "Error generating transcripts");
                    }
                }
            });
        }
    });
    
    // Download transcript
    $(document).on("click", ".download-transcript", function() {
        const transcriptId = $(this).data("id");
        window.location.href = relative_path("admin/ajax/grading.php?submit=download_transcript&id=" + transcriptId);
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
