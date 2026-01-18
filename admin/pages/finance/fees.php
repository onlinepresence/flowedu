<?php
require_once relative_path("includes/components.php");

$title = 'Fee Structure Management'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Fee Structure Configuration -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-money-bill-wave mr-2"></i>Configure Fee Structure
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="fee-structure-form">
            <?= input("hidden", "", "request_type", "update_fee_structure") ?>
            
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
            
            <!-- Fee Categories -->
            <div class="mt-6">
                <h4 class="mb-4 text-md font-semibold text-gray-700 dark:text-gray-200">Fee Categories</h4>
                <div id="fee-categories" class="space-y-4">
                    <!-- Fee categories will be added dynamically or loaded from database -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?= input("text", "Tuition Fee", "tuition_fee", "", true, attribute("placeholder", "Amount")) ?>
                        <?= input("text", "Library Fee", "library_fee", "", false, attribute("placeholder", "Amount")) ?>
                        <?= input("text", "Lab Fee", "lab_fee", "", false, attribute("placeholder", "Amount")) ?>
                        <?= input("text", "Medical Fee", "medical_fee", "", false, attribute("placeholder", "Amount")) ?>
                        <?= input("text", "Sports Fee", "sports_fee", "", false, attribute("placeholder", "Amount")) ?>
                        <?= input("text", "Examination Fee", "examination_fee", "", false, attribute("placeholder", "Amount")) ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <?= button("submit", "Save Fee Structure", "submit", "update_fee_structure", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Fee Structure List -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Fee Structures
        </h3>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Program") ?>
                <?= th("Level") ?>
                <?= th("Session") ?>
                <?= th("Total Amount") ?>
                <?= th("Effective Date") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading fee structures...", 6) ?>
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
