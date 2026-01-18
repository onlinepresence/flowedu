<?php
require_once relative_path("includes/components.php");

$title = 'Student List'; // Set the page title
$user = user();

// Mock students data - replace with actual database queries
$students_data = [
    [
        'index_number' => 'CS/2020/001',
        'name' => 'John Doe',
        'level' => 100,
        'course_code' => 'CS 101',
        'attendance_rate' => 95.5,
        'last_seen' => '2025-01-20'
    ],
    [
        'index_number' => 'CS/2020/002',
        'name' => 'Jane Smith',
        'level' => 100,
        'course_code' => 'CS 101',
        'attendance_rate' => 88.2,
        'last_seen' => '2025-01-19'
    ],
    [
        'index_number' => 'CS/2020/045',
        'name' => 'Michael Brown',
        'level' => 300,
        'course_code' => 'CS 301',
        'attendance_rate' => 92.1,
        'last_seen' => '2025-01-20'
    ]
];

$selected_course = $_GET['course'] ?? null;

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-users mr-2"></i>My Students
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                View and manage students enrolled in your courses
            </p>
        </div>
        <a href="<?= url('teacher/attendance') ?>" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
            <i class="fas fa-check-circle mr-1"></i>Take Attendance
        </a>
    </div>

    <!-- Filters -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Course
                </label>
                <select id="course-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Courses</option>
                    <option value="CS 101" <?= $selected_course === 'CS 101' ? 'selected' : '' ?>>CS 101 - Introduction to Computer Science</option>
                    <option value="CS 301" <?= $selected_course === 'CS 301' ? 'selected' : '' ?>>CS 301 - Database Systems</option>
                    <option value="MATH 201" <?= $selected_course === 'MATH 201' ? 'selected' : '' ?>>MATH 201 - Mathematics for Computing</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Search by Name/Index
                </label>
                <?= input("text", "", "search", "", false, attribute("placeholder", "Search...")) ?>
            </div>
            <div class="flex items-end">
                <?= button("button", "Reset Filters", attributes: array_merge(
                    attribute("id", "reset-filters"),
                    attribute("class", "w-full")
                )) ?>
            </div>
        </div>
    </div>

    <?php if (empty($students_data)): ?>
        <?= placeholder_element(
            "No Students Found",
            "No students are currently enrolled in your courses or match your filter criteria.",
            "fas fa-user-slash"
        ) ?>
    <?php else: ?>
        <!-- Students Table -->
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Index Number") ?>
                <?= th("Name") ?>
                <?= th("Course") ?>
                <?= th("Level") ?>
                <?= th("Attendance") ?>
                <?= th("Last Seen") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?php foreach ($students_data as $student): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <?= td(htmlspecialchars($student['index_number']), attributes: attribute('class', 'font-mono text-sm')) ?>
                        <?= td(htmlspecialchars($student['name']), attributes: attribute('class', 'font-medium')) ?>
                        <?= td(htmlspecialchars($student['course_code'])) ?>
                        <?= td('Level ' . $student['level']) ?>
                        <?php
                            $attendance_html = "
                                <div class=\"flex items-center\">
                                    <span class=\"mr-2\">".number_format($student['attendance_rate'], 1) . "%</span>
                                    <div class=\"flex-1 h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[100px]\">
                                        <div class=\"h-2 rounded-full " . ($student['attendance_rate'] >= 80 ? 'bg-green-500' : ($student['attendance_rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-500')) . "\" 
                                            style=\"width: " . $student['attendance_rate'] . "%\"></div>
                                    </div>
                                </div>
                            ";
                        ?>
                        <?= td($attendance_html) ?>
                        <?= td(date('M d, Y', strtotime($student['last_seen']))) ?>
                        <?= td_actions(array_merge(
                            create_td_action("fas fa-chart-line", "View Performance", attribute("class", "text-blue-600 hover:text-blue-800 dark:text-blue-400 px-3 py-1")),
                            create_td_action("fas fa-user", "View Profile", attribute("class", "text-green-600 hover:text-green-800 dark:text-green-400 px-3 py-1"))
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
            window.location.href = relative_path("teacher/students") + "?course=" + encodeURIComponent(course);
        } else {
            window.location.href = relative_path("teacher/students");
        }
    });
    
    $("#reset-filters").click(function(){
        window.location.href = relative_path("teacher/students");
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
