<?php
require_once relative_path("includes/components.php");

$title = 'My Timetable'; // Set the page title
$page_title = 'My Class Timetable';
$user = user();

// Mock timetable data - replace with actual database queries
$timetable_data = [
    [
        'day' => 'Monday',
        'time' => '08:00 - 10:00',
        'course' => 'Introduction to Computer Science',
        'code' => 'CS 101',
        'venue' => 'LT 1',
        'level' => 100
    ],
    [
        'day' => 'Monday',
        'time' => '10:00 - 12:00',
        'course' => 'Database Systems',
        'code' => 'CS 301',
        'venue' => 'Lab 3',
        'level' => 300
    ],
    [
        'day' => 'Tuesday',
        'time' => '08:00 - 10:00',
        'course' => 'Mathematics for Computing',
        'code' => 'MATH 201',
        'venue' => 'LT 2',
        'level' => 200
    ],
    [
        'day' => 'Wednesday',
        'time' => '14:00 - 16:00',
        'course' => 'Introduction to Computer Science',
        'code' => 'CS 101',
        'venue' => 'Lab 1',
        'level' => 100
    ],
    [
        'day' => 'Thursday',
        'time' => '10:00 - 12:00',
        'course' => 'Database Systems',
        'code' => 'CS 301',
        'venue' => 'LT 1',
        'level' => 300
    ],
    [
        'day' => 'Friday',
        'time' => '08:00 - 10:00',
        'course' => 'Mathematics for Computing',
        'code' => 'MATH 201',
        'venue' => 'LT 2',
        'level' => 200
    ]
];

// Group by day
$timetable_by_day = [];
foreach ($timetable_data as $item) {
    $timetable_by_day[$item['day']][] = $item;
}

$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Timetable Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Your scheduled classes for the current academic session
        </p>
    </div>

    <?php if (empty($timetable_data)): ?>
        <?= placeholder_element(
            "No Timetable Available",
            "You don't have any scheduled classes yet. Timetable will be updated when course assignments are made.",
            "fas fa-calendar-times"
        ) ?>
    <?php else: ?>
        <!-- Timetable by Day -->
        <?php foreach ($days_order as $day): ?>
            <?php if (isset($timetable_by_day[$day])): ?>
                <div class="mb-6 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                        <i class="fas fa-calendar-day mr-2"></i><?= $day ?>
                    </h3>
                    
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($timetable_by_day[$day] as $class): ?>
                            <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                            <?= htmlspecialchars($class['course']) ?>
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <?= htmlspecialchars($class['code']) ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">
                                        Level <?= $class['level'] ?>
                                    </span>
                                </div>
                                
                                <div class="mt-3 space-y-1 text-sm">
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-clock w-4 mr-2"></i>
                                        <span><?= htmlspecialchars($class['time']) ?></span>
                                    </div>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-map-marker-alt w-4 mr-2"></i>
                                        <span><?= htmlspecialchars($class['venue']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <a href="<?= url('teacher/students') ?>?course=<?= urlencode($class['code']) ?>" 
                                       class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        <i class="fas fa-users mr-1"></i>View Students
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
