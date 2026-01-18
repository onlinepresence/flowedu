<?php
require_once relative_path("includes/components.php");

$title = 'My Courses'; // Set the page title
$page_title = 'My Assigned Courses';
$user = user();

// Mock courses data - replace with actual database queries
$courses_data = [
    [
        'code' => 'CS 101',
        'name' => 'Introduction to Computer Science',
        'program' => 'BSc Computer Science',
        'level' => 100,
        'semester' => 'First Semester',
        'students_count' => 45,
        'status' => 'active'
    ],
    [
        'code' => 'CS 301',
        'name' => 'Database Systems',
        'program' => 'BSc Computer Science',
        'level' => 300,
        'semester' => 'First Semester',
        'students_count' => 32,
        'status' => 'active'
    ],
    [
        'code' => 'MATH 201',
        'name' => 'Mathematics for Computing',
        'program' => 'BSc Computer Science',
        'level' => 200,
        'semester' => 'Second Semester',
        'students_count' => 38,
        'status' => 'active'
    ]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            View all courses assigned to you for the current academic session
        </p>
    </div>

    <?php if (empty($courses_data)): ?>
        <?= placeholder_element(
            "No Courses Assigned",
            "You have not been assigned to any courses yet. Please contact the administration for course assignments.",
            "fas fa-book"
        ) ?>
    <?php else: ?>
        <!-- Courses Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($courses_data as $course): ?>
                <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                <?= htmlspecialchars($course['name']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <?= htmlspecialchars($course['code']) ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $course['status'] === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800' ?>">
                            <?= ucfirst($course['status']) ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center">
                            <i class="fas fa-graduation-cap w-4 mr-2"></i>
                            <span><?= htmlspecialchars($course['program']) ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-layer-group w-4 mr-2"></i>
                            <span>Level <?= $course['level'] ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt w-4 mr-2"></i>
                            <span><?= htmlspecialchars($course['semester']) ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-users w-4 mr-2"></i>
                            <span><?= $course['students_count'] ?> students</span>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                        <a href="<?= url('teacher/students') ?>?course=<?= urlencode($course['code']) ?>" 
                           class="flex-1 text-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-users mr-1"></i>View Students
                        </a>
                        <a href="<?= url('teacher/courses/materials') ?>?course=<?= urlencode($course['code']) ?>" 
                           class="flex-1 text-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 transition-colors">
                            <i class="fas fa-folder mr-1"></i>Materials
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
