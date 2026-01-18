<?php
require_once relative_path("includes/components.php");

$title = 'Student Performance'; // Set the page title
$user = user();

$selected_student = $_GET['student'] ?? null;

// Mock performance data - replace with actual database queries
$performance_data = null;
if ($selected_student) {
    $performance_data = [
        'index_number' => $selected_student,
        'name' => 'John Doe',
        'overall_gpa' => 3.75,
        'courses' => [
            [
                'code' => 'CS 101',
                'name' => 'Introduction to Computer Science',
                'score' => 85,
                'grade' => 'A',
                'attendance' => 95.5
            ],
            [
                'code' => 'CS 301',
                'name' => 'Database Systems',
                'score' => 78,
                'grade' => 'B+',
                'attendance' => 92.1
            ]
        ]
    ];
}

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            View detailed performance metrics for your students
        </p>
    </div>

    <!-- Student Search -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <form method="GET" class="flex gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Search Student (Index Number or Name)
                </label>
                <?= input("text", "", "student", $selected_student ?? "", false, attribute("placeholder", "Enter index number or name")) ?>
            </div>
            <div class="flex items-end">
                <?= button("submit", "View Performance", "submit", "", "blue") ?>
            </div>
        </form>
    </div>

    <?php if (!$selected_student): ?>
        <?= placeholder_element(
            "Search for Student",
            "Enter a student's index number or name above to view their performance metrics and progress.",
            "fas fa-search"
        ) ?>
    <?php elseif (!$performance_data): ?>
        <?= placeholder_element(
            "Student Not Found",
            "No student found matching your search. Please verify the index number or name and try again.",
            "fas fa-user-slash"
        ) ?>
    <?php else: ?>
        <!-- Student Overview -->
        <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($performance_data['name']) ?>
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Index: <?= htmlspecialchars($performance_data['index_number']) ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Overall GPA</div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        <?= number_format($performance_data['overall_gpa'], 2) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance by Course -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">
                Performance by Course
            </h3>
            
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($performance_data['courses'] as $course): ?>
                    <div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            <?= htmlspecialchars($course['code']) ?>
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <?= htmlspecialchars($course['name']) ?>
                        </p>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Score:</span>
                                <span class="font-semibold"><?= $course['score'] ?>%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Grade:</span>
                                <span class="font-semibold"><?= $course['grade'] ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Attendance:</span>
                                <span class="font-semibold"><?= number_format($course['attendance'], 1) ?>%</span>
                            </div>
                        </div>
                        
                        <!-- Progress bar for score -->
                        <div class="mt-4">
                            <div class="flex justify-between text-xs mb-1">
                                <span>Score Progress</span>
                                <span><?= $course['score'] ?>%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-blue-500" style="width: <?= $course['score'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Attendance Chart Area -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">
                Attendance Trend
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Chart visualization would be displayed here showing attendance trends over time.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
