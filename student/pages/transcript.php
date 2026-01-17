<?php
require_once relative_path("includes/components.php");

$title = 'My Transcript'; // Set the page title
$user = user();

// Mock transcript data - replace with actual database queries
$transcript_data = [
    [
        'semester' => 'First Semester 2023/2024',
        'courses' => [
            ['code' => 'CS 101', 'name' => 'Introduction to Computer Science', 'credit_hours' => 3, 'grade' => 'A', 'points' => 12],
            ['code' => 'MATH 201', 'name' => 'Mathematics for Computing', 'credit_hours' => 3, 'grade' => 'B+', 'points' => 10.5],
            ['code' => 'CS 201', 'name' => 'Data Structures', 'credit_hours' => 3, 'grade' => 'A-', 'points' => 11.25],
            ['code' => 'ENG 101', 'name' => 'English Communication', 'credit_hours' => 2, 'grade' => 'B', 'points' => 6],
        ]
    ],
    [
        'semester' => 'Second Semester 2023/2024',
        'courses' => [
            ['code' => 'CS 301', 'name' => 'Database Systems', 'credit_hours' => 3, 'grade' => 'A', 'points' => 12],
            ['code' => 'CS 401', 'name' => 'Web Development', 'credit_hours' => 3, 'grade' => 'A-', 'points' => 11.25],
            ['code' => 'CS 501', 'name' => 'Software Engineering', 'credit_hours' => 3, 'grade' => 'B+', 'points' => 10.5],
            ['code' => 'STAT 201', 'name' => 'Statistics', 'credit_hours' => 2, 'grade' => 'B', 'points' => 6],
        ]
    ]
];

$transcript_data = [];

// Calculate totals
$total_credit_hours = 0;
$total_points = 0;
foreach ($transcript_data as $semester) {
    foreach ($semester['courses'] as $course) {
        $total_credit_hours += $course['credit_hours'];
        $total_points += $course['points'];
    }
}
$cgpa = $total_credit_hours > 0 ? number_format($total_points / $total_credit_hours, 2) : '0.00';

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Transcript Header -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                    Academic Transcript
                </h2>
                <div class="space-y-2 text-sm">
                    <p class="text-gray-600 dark:text-gray-400">
                        <strong>Name:</strong> <?= htmlspecialchars($user['lastname'] . ' ' . $user['othernames']) ?>
                    </p>
                    <p class="text-gray-600 dark:text-gray-400">
                        <strong>Index Number:</strong> <?= htmlspecialchars($user['index_number'] ?? 'N/A') ?>
                    </p>
                    <p class="text-gray-600 dark:text-gray-400">
                        <strong>Program:</strong> <?= htmlspecialchars(get_program($user['program_id'], 'name') ?? 'N/A') ?>
                    </p>
                    <p class="text-gray-600 dark:text-gray-400">
                        <strong>Level:</strong> <?= htmlspecialchars($user['current_year'] ?? 'N/A') ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-center">
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Cumulative GPA</p>
                    <p class="text-4xl font-bold text-purple-600 dark:text-purple-400"><?= $cgpa ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                        Total Credit Hours: <?= $total_credit_hours ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($transcript_data)): ?>
        <?= placeholder_element(
            "No Transcript Available",
            "Your transcript will be available once you have completed courses and received grades.",
            "fas fa-file-alt"
        ) ?>
    <?php else: ?>
        <!-- Transcript by Semester -->
        <?php foreach ($transcript_data as $semester): ?>
            <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200 border-b pb-2">
                    <?= htmlspecialchars($semester['semester']) ?>
                </h3>

                <?= table_start() ?>
                    <?= thead_start() ?>
                        <?= th("Course Code") ?>
                        <?= th("Course Name") ?>
                        <?= th("Credit Hours") ?>
                        <?= th("Grade") ?>
                        <?= th("Grade Points") ?>
                    <?= thead_end() ?>

                    <?= tbody_start() ?>
                        <?php 
                        $semester_credit_hours = 0;
                        $semester_points = 0;
                        foreach ($semester['courses'] as $course): 
                            $semester_credit_hours += $course['credit_hours'];
                            $semester_points += $course['points'];
                        ?>
                            <?= tr_start() ?>
                                <?= td($course['code']) ?>
                                <?= td($course['name']) ?>
                                <?= td($course['credit_hours']) ?>
                                <?= td($course['grade'], attributes: attribute("class", "font-semibold")) ?>
                                <?= td($course['points']) ?>
                            <?= tr_end() ?>
                        <?php endforeach; ?>
                        
                        <!-- Semester Summary Row -->
                        <?= tr_start(attribute("class", "bg-gray-50 dark:bg-gray-700 font-semibold")) ?>
                            <?= td("Total", attributes: attribute("colspan", "2")) ?>
                            <?= td($semester_credit_hours) ?>
                            <?= td("GPA", attributes: attribute("colspan", "1")) ?>
                            <?= td(number_format($semester_points / $semester_credit_hours, 2)) ?>
                        <?= tr_end() ?>
                    <?= tbody_end() ?>
                <?= table_end() ?>
            </div>
        <?php endforeach; ?>

        <!-- Download/Print Options -->
        <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <div class="flex flex-wrap gap-4">
                <button 
                    onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"
                >
                    <i class="fas fa-print mr-2"></i>
                    Print Transcript
                </button>
                <button 
                    onclick="downloadTranscript()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:shadow-outline-blue"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download PDF
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $scripts = <<<HTML
<script>
    function downloadTranscript() {
        // This would typically generate a PDF on the server
        alert('PDF download functionality will be implemented. This will generate an official transcript PDF.');
    }
</script>
HTML;
?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
