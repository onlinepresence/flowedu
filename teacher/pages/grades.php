<?php
require_once relative_path("includes/components.php");

$title = 'Grade Submissions'; // Set the page title
$user = user();

// Mock submissions data - replace with actual database queries
$submissions_data = [
    [
        'assignment_title' => 'Assignment 1: Introduction to Programming',
        'course_code' => 'CS 101',
        'due_date' => '2025-01-25',
        'submitted' => 42,
        'pending' => 3,
        'total_students' => 45,
        'status' => 'grading'
    ],
    [
        'assignment_title' => 'Database Design Project',
        'course_code' => 'CS 301',
        'due_date' => '2025-01-28',
        'submitted' => 30,
        'pending' => 2,
        'total_students' => 32,
        'status' => 'review'
    ],
    [
        'assignment_title' => 'Midterm Exam',
        'course_code' => 'MATH 201',
        'due_date' => '2025-02-05',
        'submitted' => 38,
        'pending' => 0,
        'total_students' => 38,
        'status' => 'completed'
    ]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Review and grade student assignments and submissions
            </p>
        </div>
        <?= button("button", "Create Assignment", attributes: array_merge(
            attribute("id", "create-assignment-btn"),
            attribute("class", "max-w-xs")
        )) ?>
    </div>

    <?php if (empty($submissions_data)): ?>
        <?= placeholder_element(
            "No Submissions",
            "You don't have any assignments with student submissions to grade yet.",
            "fas fa-clipboard"
        ) ?>
    <?php else: ?>
        <!-- Submissions List -->
        <div class="grid gap-6">
            <?php foreach ($submissions_data as $submission): ?>
                <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                <?= htmlspecialchars($submission['assignment_title']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <?= htmlspecialchars($submission['course_code']) ?> • Due: <?= date('M d, Y', strtotime($submission['due_date'])) ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= 
                            $submission['status'] === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                            ($submission['status'] === 'grading' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') 
                        ?>">
                            <?= ucfirst($submission['status']) ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="p-3 bg-blue-50 rounded-lg dark:bg-blue-900">
                            <div class="text-sm text-blue-800 dark:text-blue-200">Submitted</div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                <?= $submission['submitted'] ?>
                            </div>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg dark:bg-red-900">
                            <div class="text-sm text-red-800 dark:text-red-200">Pending</div>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                <?= $submission['pending'] ?>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <div class="text-sm text-gray-800 dark:text-gray-200">Total Students</div>
                            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                                <?= $submission['total_students'] ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-xs mb-1">
                            <span>Submission Progress</span>
                            <span><?= round(($submission['submitted'] / $submission['total_students']) * 100, 1) ?>%</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                            <div class="h-2 rounded-full bg-blue-500" style="width: <?= ($submission['submitted'] / $submission['total_students']) * 100 ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                            <i class="fas fa-check-circle mr-1"></i>Grade Submissions
                        </button>
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                            <i class="fas fa-eye mr-1"></i>View All
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    $("#create-assignment-btn").click(function(){
        alert_box("Assignment creation feature will be implemented", "info");
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
