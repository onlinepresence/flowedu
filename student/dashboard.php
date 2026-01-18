<?php
require_once relative_path("includes/components.php");

$title = 'Dashboard';
$user = user();

// "Welcome" message title
$page_title = "Welcome {$user['lastname']},";

// Calculate CGPA (replace with actual calculation when available)
$cgpa = "0.00";
$total_credit_hours = 0;
$total_points = 0;
if (function_exists("student_gpa_stats")) {
    $gpa_stats = student_gpa_stats($user["id"]);
    $cgpa = $gpa_stats["cgpa"] ?? "0.00";
    $total_credit_hours = $gpa_stats["credit_hours"] ?? 0;
    $total_points = $gpa_stats["points"] ?? 0;
}

// Outstanding Fees (replace with actual computation when available)
$outstanding_fees = 0.0;
if (function_exists("student_outstanding_fees")) {
    $outstanding_fees = student_outstanding_fees($user["id"]);
}
$outstanding_fees_display = "GHC " . number_format($outstanding_fees, 2);

// Next Clearance eligibility
$final_year_label = null;
if (function_exists("get_program")) {
    $program = get_program($user["program_id"], "program_length");
    $final_year = (isset($program["program_length"]) ? $program["program_length"] : 0) * 100;
    if ($final_year != 0 && isset($user["current_year"])) {
        $final_year_label = ($user["current_year"] == $final_year)
            ? "<span class='font-semibold text-green-600'><i class='fas fa-check-circle'></i> Eligible for Clearance</span>"
            : "<span class='text-gray-500 text-sm'>Eligible in Level {$final_year}</span>";
    }
}

// Start output buffering to capture the content
ob_start();
?>

<div class="mb-6">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        <?= htmlspecialchars($user["othernames"] ?? "") ?>
    </div>
</div>

<!-- cards -->
<?= card_container_start() ?>
    <?= dashboard_card_btn("Index Number", $user["index_number"], "fas fa-id-card", "purple") ?>
    <?= dashboard_card_btn("Current Level", $user["graduated"] ? "Graduated" : "Level ".$user["current_year"], "fas fa-user-graduate") ?>
    <?= dashboard_card_btn("CGPA", $cgpa, "fas fa-star", "blue") ?>
    <?= dashboard_card_btn("Outstanding Fees", $outstanding_fees_display, "fas fa-wallet", $outstanding_fees > 0 ? "red" : "green") ?>
<?= card_container_end() ?>

<?php if ($final_year_label): ?>
    <div class="mb-6">
        <div class="p-4 rounded bg-white dark:bg-gray-800 shadow flex items-center gap-4">
            <i class="fas fa-unlock-alt text-purple-500 text-2xl"></i>
            <span><?= $final_year_label ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Links -->
<div class="mb-6">
    <h2 class="mb-2 text-lg font-bold text-gray-700 dark:text-gray-200">Quick Actions</h2>
    <div class="flex flex-wrap gap-4">
        <a href="<?= route('student.profile') ?>" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm flex items-center gap-2">
            <i class="fas fa-id-badge"></i> My Profile
        </a>
        <a href="<?= route('student.fees') ?>" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm flex items-center gap-2">
            <i class="fas fa-wallet"></i> Fees
        </a>
        <a href="<?= route('student.courses') ?>" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm flex items-center gap-2">
            <i class="fas fa-book"></i> Registered Courses
        </a>
        <a href="<?= route('student.transcript') ?>" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm flex items-center gap-2">
            <i class="fas fa-file-alt"></i> Transcript
        </a>
        <a href="<?= route('student.clearance') ?>" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700 text-sm flex items-center gap-2">
            <i class="fas fa-unlock"></i> Clearance
        </a>
        <a href="<?= route('student.results') ?>" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm flex items-center gap-2">
            <i class="fas fa-poll"></i> Results
        </a>
    </div>
</div>

<!-- Announcements / Placeholder for recent news -->
<div class="mb-6">
    <h2 class="mb-2 text-lg font-bold text-gray-700 dark:text-gray-200">Announcements</h2>
    <?= placeholder_element(
        "No Announcements",
        "Announcements and important information will appear here as soon as they are available.",
        "fas fa-bullhorn"
    ) ?>
</div>

<!-- course registration table -->
<?= table_start() ?>
    <?= thead_start() ?>
        <?= th("Course Code") ?>
        <?= th("Course Name") ?>
        <?= th("Credit Hours") ?>
    <?= thead_end() ?>

    <?= tbody_start() ?>
        <?= tr_start() ?>
            <?= td_empty("Course registration is completed via the external portal. Please visit <a href='https://studentioe.ucc.edu.gh/login.php' target='_blank' class=\"text-blue-500 underline\">this link</a> to register for your courses.", 3) ?>
        <?= tr_end() ?>
    <?= tbody_end() ?>
<?= table_end() ?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

require relative_path('layouts/auth.php');
