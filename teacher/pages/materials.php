<?php
require_once relative_path("includes/components.php");

$title = 'Course Materials'; // Set the page title
$user = user();

// Mock materials data - replace with actual database queries
$materials_data = [
    [
        'title' => 'Introduction to Programming - Lecture 1',
        'type' => 'pdf',
        'course_code' => 'CS 101',
        'uploaded_date' => '2025-01-15',
        'file_size' => '2.5 MB',
        'downloads' => 45
    ],
    [
        'title' => 'Database Design Principles',
        'type' => 'pdf',
        'course_code' => 'CS 301',
        'uploaded_date' => '2025-01-18',
        'file_size' => '3.2 MB',
        'downloads' => 32
    ],
    [
        'title' => 'Assignment 1 Guidelines',
        'type' => 'doc',
        'course_code' => 'CS 101',
        'uploaded_date' => '2025-01-20',
        'file_size' => '0.5 MB',
        'downloads' => 42
    ],
    [
        'title' => 'Practice Exercises - Week 3',
        'type' => 'pdf',
        'course_code' => 'MATH 201',
        'uploaded_date' => '2025-01-22',
        'file_size' => '1.8 MB',
        'downloads' => 38
    ]
];

// Filter by course if provided
$selected_course = $_GET['course'] ?? null;
if ($selected_course) {
    $materials_data = array_filter($materials_data, function($material) use ($selected_course) {
        return $material['course_code'] === $selected_course;
    });
}

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage and share course materials with your students
            </p>
        </div>
        <?= button("button", "Upload Material", attributes: array_merge(
            attribute("id", "upload-material-btn"),
            attribute("class", "max-w-xs"),
            attribute("type", "button")
        )) ?>
    </div>

    <!-- Course Filter -->
    <?php if (!$selected_course): ?>
        <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Filter by Course
            </label>
            <select id="course-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Courses</option>
                <option value="CS 101">CS 101 - Introduction to Computer Science</option>
                <option value="CS 301">CS 301 - Database Systems</option>
                <option value="MATH 201">MATH 201 - Mathematics for Computing</option>
            </select>
        </div>
    <?php endif; ?>

    <?php if (empty($materials_data)): ?>
        <?= placeholder_element(
            "No Materials Available",
            "You haven't uploaded any course materials yet. Click 'Upload Material' to get started.",
            "fas fa-file-upload"
        ) ?>
    <?php else: ?>
        <!-- Materials Table -->
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Title") ?>
                <?= th("Course") ?>
                <?= th("Type") ?>
                <?= th("Uploaded") ?>
                <?= th("Size") ?>
                <?= th("Downloads") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?php foreach ($materials_data as $material): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <?= td(htmlspecialchars($material['title']), attributes: attribute('class', 'font-medium')) ?>
                        <?= td(htmlspecialchars($material['course_code'])) ?>
                        <?= td(strtoupper($material['type'])) ?>
                        <?= td(date('M d, Y', strtotime($material['uploaded_date']))) ?>
                        <?= td($material['file_size']) ?>
                        <?= td($material['downloads']) ?>
                        <?= td_actions(array_merge(
                            create_td_action(
                                "fas fa-download", 
                                "Download", 
                                attribute("class", "text-blue-600 hover:text-blue-800 dark:text-blue-400 px-3 py-1")
                            ),
                            create_td_action(
                                "fas fa-edit", 
                                "Edit", 
                                attribute("class", "text-green-600 hover:text-green-800 dark:text-green-400 px-3 py-1")
                            ),
                            create_td_action(
                                "fas fa-trash", 
                                "Delete", 
                                attribute("class", "text-red-600 hover:text-red-800 dark:text-red-400 px-3 py-1")
                            ),
                        )) ?>
                    </tr>
                <?php endforeach; ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    <?php endif; ?>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    $("#course-filter").change(function(){
        const course = $(this).val();
        if(course) {
            window.location.href = relative_path("teacher/courses/materials") + "?course=" + encodeURIComponent(course);
        } else {
            window.location.href = relative_path("teacher/courses/materials");
        }
    });
    
    $("#upload-material-btn").click(function(){
        // Open upload modal
        alert_box("Upload functionality will be implemented", "info");
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
