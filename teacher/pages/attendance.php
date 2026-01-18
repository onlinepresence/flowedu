<?php
require_once relative_path("includes/components.php");

$title = 'Attendance Management'; // Set the page title
$user = user();

// Mock courses data - replace with actual database queries
$courses_data = [
    ['code' => 'CS 101', 'name' => 'Introduction to Computer Science', 'level' => 100],
    ['code' => 'CS 301', 'name' => 'Database Systems', 'level' => 300],
    ['code' => 'MATH 201', 'name' => 'Mathematics for Computing', 'level' => 200]
];

$selected_course = $_GET['course'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Mock attendance records for selected course
$attendance_records = [];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Mark and manage student attendance for your classes
        </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select Course
                </label>
                <select name="course" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses_data as $course): ?>
                        <option value="<?= htmlspecialchars($course['code']) ?>" <?= $selected_course === $course['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['code']) ?> - <?= htmlspecialchars($course['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <?= input("date", "Date", "date", $selected_date, true) ?>
            </div>
            <div class="flex items-end">
                <?= button("submit", "Load Students", "submit", "", "blue") ?>
            </div>
        </form>
    </div>

    <?php if (!$selected_course): ?>
        <?= placeholder_element(
            "Select Course",
            "Please select a course and date to view/manage attendance for that class.",
            "fas fa-clipboard-check"
        ) ?>
    <?php elseif (empty($attendance_records)): ?>
        <?= placeholder_element(
            "No Records Found",
            "No attendance records found for the selected course and date. You can mark attendance for today's class.",
            "fas fa-calendar-times"
        ) ?>
    <?php else: ?>
        <!-- Attendance Form -->
        <form method="POST" action="<?= url('teacher/submit.php') ?>" class="mb-6">
            <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        Mark Attendance - <?= htmlspecialchars($selected_course) ?> - <?= date('F d, Y', strtotime($selected_date)) ?>
                    </h3>
                </div>
                
                <!-- This would be populated with actual student list via AJAX -->
                <div class="space-y-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Student list will be loaded here. Checkboxes for present/absent/excused will be displayed.
                    </p>
                </div>
                
                <div class="mt-6 flex gap-4">
                    <?= button("submit", "Save Attendance", "submit", "save_attendance", "green") ?>
                    <?= button("button", "Cancel", attributes: array_merge(
                        attribute("type", "button"),
                        attribute("onclick", "window.history.back()")
                    )) ?>
                </div>
            </div>
        </form>

        <!-- Attendance Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-4 bg-green-50 rounded-lg dark:bg-green-900">
                <div class="text-sm text-green-800 dark:text-green-200">Present</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">0</div>
            </div>
            <div class="p-4 bg-red-50 rounded-lg dark:bg-red-900">
                <div class="text-sm text-red-800 dark:text-red-200">Absent</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">0</div>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg dark:bg-yellow-900">
                <div class="text-sm text-yellow-800 dark:text-yellow-200">Excused</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">0</div>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg dark:bg-blue-900">
                <div class="text-sm text-blue-800 dark:text-blue-200">Total</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
