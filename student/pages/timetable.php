<?php
require_once relative_path("includes/components.php");

$title = 'My Timetable'; // Set the page title
$user = user();

// Mock timetable data - replace with actual database queries
$timetable_data = [
    [
        'day' => 'Monday',
        'time' => '08:00 - 10:00',
        'course' => 'Introduction to Computer Science',
        'code' => 'CS 101',
        'venue' => 'LT 1',
        'lecturer' => 'Dr. John Smith'
    ],
    [
        'day' => 'Monday',
        'time' => '10:00 - 12:00',
        'course' => 'Mathematics for Computing',
        'code' => 'MATH 201',
        'venue' => 'LT 2',
        'lecturer' => 'Prof. Jane Doe'
    ],
    [
        'day' => 'Tuesday',
        'time' => '08:00 - 10:00',
        'course' => 'Database Systems',
        'code' => 'CS 301',
        'venue' => 'Lab 3',
        'lecturer' => 'Dr. Michael Brown'
    ],
    [
        'day' => 'Wednesday',
        'time' => '14:00 - 16:00',
        'course' => 'Web Development',
        'code' => 'CS 401',
        'venue' => 'Lab 1',
        'lecturer' => 'Dr. Sarah Johnson'
    ],
    [
        'day' => 'Thursday',
        'time' => '10:00 - 12:00',
        'course' => 'Software Engineering',
        'code' => 'CS 501',
        'venue' => 'LT 3',
        'lecturer' => 'Prof. David Wilson'
    ],
    [
        'day' => 'Friday',
        'time' => '08:00 - 10:00',
        'course' => 'Data Structures',
        'code' => 'CS 201',
        'venue' => 'LT 2',
        'lecturer' => 'Dr. Emily Davis'
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
        <?= information_bar(
            "This is your current semester timetable. Please note that changes may occur, and you will be notified of any updates.",
            "info",
            false,
            attribute("class", "mb-4")
        ) ?>
    </div>

    <?php if (empty($timetable_data)): ?>
        <?= placeholder_element(
            "No Timetable Available",
            "Your timetable has not been published yet. Please check back later or contact your academic advisor.",
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
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-user-tie w-4 mr-2"></i>
                                        <span><?= htmlspecialchars($class['lecturer']) ?></span>
                                    </div>
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
