<?php
require_once relative_path("includes/components.php");

$title = 'Upload Results'; // Set the page title
$page_title = 'Upload Student Results';

$user = user();

// Mock courses data - replace with actual database queries
$courses_data = [
    ['code' => 'CS 101', 'name' => 'Introduction to Computer Science', 'level' => 100],
    ['code' => 'CS 301', 'name' => 'Database Systems', 'level' => 300],
    ['code' => 'MATH 201', 'name' => 'Mathematics for Computing', 'level' => 200]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <?= information_bar(
        "You can upload results in Excel format (XLSX) or enter them manually. Download the template below to ensure correct format.",
        "info",
        false,
        attribute("class", "mb-6")
    ) ?>
    
    <!-- Page Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Upload student results via Excel file or enter manually
        </p>
    </div>

    <!-- Upload Section -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-file-excel mr-2"></i>Upload Excel File
        </h3>
        
        <form method="POST" action="<?= url('teacher/submit.php') ?>" enctype="multipart/form-data" class="space-y-6">
            <?= input("hidden", "", "submit", "upload_results") ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Course
                    </label>
                    <select name="course_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses_data as $course): ?>
                            <option value="<?= htmlspecialchars($course['code']) ?>">
                                <?= htmlspecialchars($course['code']) ?> - <?= htmlspecialchars($course['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Academic Session
                    </label>
                    <?php 
                        $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "name", true);
                        $session_options = [];
                        if(is_array($sessions) && !empty($sessions)) {
                            foreach($sessions as $session) {
                                $session_options[] = ["id" => $session['id'], "text" => $session['name']];
                            }
                        }
                    ?>
                    <?= select("session_id", "", $session_options, "Select Session", required: true) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Results File (Excel)
                </label>
                <?= input("file", "", "results_file", "", true, attribute("accept", ".xlsx,.xls")) ?>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <a href="#" class="text-blue-600 hover:underline">
                        <i class="fas fa-download mr-1"></i>Download template file
                    </a>
                </p>
            </div>
            
            <div class="flex gap-4">
                <?= button("submit", "Upload Results", "submit", "upload_results", "green") ?>
                <?= button("button", "Cancel", attributes: array_merge(
                    attribute("type", "button"),
                    attribute("onclick", "window.history.back()")
                )) ?>
            </div>
        </form>
    </div>

    <!-- Manual Entry Section -->
    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-keyboard mr-2"></i>Manual Entry
        </h3>
        
        <div class="mb-4">
            <a href="<?= url('admin/grading/enter') ?>" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-1"></i>Enter Results Manually
            </a>
        </div>
        
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Use the manual entry option to input individual student results or make corrections.
        </p>
    </div>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
