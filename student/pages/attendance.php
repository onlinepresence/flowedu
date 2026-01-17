<?php
require_once relative_path("includes/components.php");

$title = 'Attendance'; // Set the page title
$user = user();

// Mock attendance data - replace with actual database queries
$attendance_data = [
    [
        'course_code' => 'CS 101',
        'course_name' => 'Introduction to Computer Science',
        'total_classes' => 30,
        'attended' => 28,
        'absent' => 2,
        'percentage' => 93.33,
        'status' => 'good'
    ],
    [
        'course_code' => 'MATH 201',
        'course_name' => 'Mathematics for Computing',
        'total_classes' => 30,
        'attended' => 25,
        'absent' => 5,
        'percentage' => 83.33,
        'status' => 'warning'
    ],
    [
        'course_code' => 'CS 301',
        'course_name' => 'Database Systems',
        'total_classes' => 28,
        'attended' => 28,
        'absent' => 0,
        'percentage' => 100.00,
        'status' => 'excellent'
    ],
    [
        'course_code' => 'CS 401',
        'course_name' => 'Web Development',
        'total_classes' => 30,
        'attended' => 22,
        'absent' => 8,
        'percentage' => 73.33,
        'status' => 'poor'
    ]
];
$attendance_data = [];

// Calculate overall attendance
$total_classes_all = 0;
$total_attended_all = 0;
foreach ($attendance_data as $record) {
    $total_classes_all += $record['total_classes'];
    $total_attended_all += $record['attended'];
}
$overall_percentage = $total_classes_all > 0 ? number_format(($total_attended_all / $total_classes_all) * 100, 2) : 0;

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Overall Attendance Summary -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Overall Attendance Summary
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Classes</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?= $total_classes_all ?></p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Classes Attended</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= $total_attended_all ?></p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Attendance Rate</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?= $overall_percentage ?>%</p>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Overall Progress</span>
                <span><?= $overall_percentage ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                <div 
                    class="bg-purple-600 h-3 rounded-full transition-all duration-300" 
                    style="width: <?= $overall_percentage ?>%"
                ></div>
            </div>
        </div>
    </div>

    <!-- Course-wise Attendance -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Attendance by Course
        </h3>

        <?php if (empty($attendance_data)): ?>
            <?= placeholder_element(
                "No Attendance Records",
                "Your attendance records will appear here once classes begin.",
                "fas fa-calendar-check"
            ) ?>
        <?php else: ?>
            <?= table_start() ?>
                <?= thead_start() ?>
                    <?= th("Course Code") ?>
                    <?= th("Course Name") ?>
                    <?= th("Total Classes") ?>
                    <?= th("Attended") ?>
                    <?= th("Absent") ?>
                    <?= th("Percentage") ?>
                    <?= th("Status") ?>
                <?= thead_end() ?>

                <?= tbody_start() ?>
                    <?php foreach ($attendance_data as $record): ?>
                        <?php
                        $status_colors = [
                            'excellent' => 'bg-green-100 text-green-800',
                            'good' => 'bg-blue-100 text-blue-800',
                            'warning' => 'bg-yellow-100 text-yellow-800',
                            'poor' => 'bg-red-100 text-red-800'
                        ];
                        $status_labels = [
                            'excellent' => 'Excellent',
                            'good' => 'Good',
                            'warning' => 'Warning',
                            'poor' => 'Poor'
                        ];
                        $status_class = $status_colors[$record['status']] ?? 'bg-gray-100 text-gray-800';
                        $status_label = $status_labels[$record['status']] ?? 'Unknown';
                        ?>
                        <?= tr_start() ?>
                            <?= td($record['course_code']) ?>
                            <?= td($record['course_name']) ?>
                            <?= td($record['total_classes']) ?>
                            <?= td($record['attended'], attributes: attribute("class", "text-green-600 font-semibold")) ?>
                            <?= td($record['absent'], attributes: attribute("class", "text-red-600 font-semibold")) ?>
                            <?= td($record['percentage'] . '%', attributes: attribute("class", "font-semibold")) ?>
                            <?= td(
                                '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $status_class . '">' . $status_label . '</span>',
                                attributes: attribute("class", "text-center")
                            ) ?>
                        <?= tr_end() ?>
                    <?php endforeach; ?>
                <?= tbody_end() ?>
            <?= table_end() ?>
        <?php endif; ?>
    </div>

    <!-- Attendance Policy Information -->
    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-info-circle mr-2"></i>Attendance Policy
        </h3>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <p><strong>Minimum Attendance:</strong> Students must maintain at least 75% attendance in each course to be eligible for examinations.</p>
            <p><strong>Warning Level:</strong> Attendance below 80% will trigger a warning notification.</p>
            <p><strong>Examination Eligibility:</strong> Students with attendance below 75% may not be allowed to sit for final examinations.</p>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Please ensure you attend classes regularly to maintain good academic standing.
            </p>
        </div>
    </div>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
