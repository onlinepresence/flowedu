<?php
require_once relative_path("includes/components.php");

$title = 'Results Approval'; // Set the page title

// Mock results pending approval - replace with actual database queries
$pending_results = [
    [
        'id' => 1,
        'course_code' => 'CS 101',
        'course_name' => 'Introduction to Computer Science',
        'teacher_name' => 'Dr. John Smith',
        'session_name' => '2024/2025',
        'level' => 100,
        'students_count' => 45,
        'submitted_date' => '2025-01-20',
        'submitted_by' => 'Dr. John Smith'
    ],
    [
        'id' => 2,
        'course_code' => 'CS 301',
        'course_name' => 'Database Systems',
        'teacher_name' => 'Prof. Jane Doe',
        'session_name' => '2024/2025',
        'level' => 300,
        'students_count' => 32,
        'submitted_date' => '2025-01-22',
        'submitted_by' => 'Prof. Jane Doe'
    ]
];

$approved_results = [];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-check-circle mr-2"></i>Results Approval
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Review and approve results submitted by teachers before they become visible to students
        </p>
    </div>

    <?= information_bar(
        "Results submitted by teachers require admin approval before they are published and visible to students. Review each submission carefully.",
        "info",
        false,
        attribute("class", "mb-6")
    ) ?>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-8">
            <button onclick="showTab('pending')" id="tab-pending" class="py-4 px-1 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600 dark:text-indigo-400">
                Pending Approval (<span id="pending-count"><?= count($pending_results) ?></span>)
            </button>
            <button onclick="showTab('approved')" id="tab-approved" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400">
                Approved Results
            </button>
        </nav>
    </div>

    <!-- Pending Results Tab -->
    <div id="pending-tab-content">
        <?php if (empty($pending_results)): ?>
            <?= placeholder_element(
                "No Pending Results",
                "All submitted results have been reviewed and approved. No pending submissions at the moment.",
                "fas fa-check-circle"
            ) ?>
        <?php else: ?>
            <!-- Pending Results Table -->
            <?= table_start() ?>
                <?= thead_start() ?>
                    <?= th("Course") ?>
                    <?= th("Teacher") ?>
                    <?= th("Level") ?>
                    <?= th("Session") ?>
                    <?= th("Students") ?>
                    <?= th("Submitted Date") ?>
                    <?= th("Actions") ?>
                <?= thead_end() ?>
                <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                    <?php foreach ($pending_results as $result): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <?= td(htmlspecialchars($result['course_code'] . ' - ' . $result['course_name']), attribute('class', 'font-medium')) ?>
                            <?= td(htmlspecialchars($result['teacher_name'])) ?>
                            <?= td('Level ' . $result['level']) ?>
                            <?= td(htmlspecialchars($result['session_name'])) ?>
                            <?= td($result['students_count'] . ' students') ?>
                            <?= td(date('M d, Y', strtotime($result['submitted_date']))) ?>
                            <?= td_start() ?>
                                <div class="flex gap-2">
                                    <button onclick="viewResults(<?= $result['id'] ?>)" class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button onclick="approveResults(<?= $result['id'] ?>)" class="px-3 py-1 text-sm text-green-600 hover:text-green-800 dark:text-green-400">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button onclick="rejectResults(<?= $result['id'] ?>)" class="px-3 py-1 text-sm text-red-600 hover:text-red-800 dark:text-red-400">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            <?= td_end() ?>
                        </tr>
                    <?php endforeach; ?>
                <?= tbody_end() ?>
            <?= table_end() ?>
        <?php endif; ?>
    </div>

    <!-- Approved Results Tab -->
    <div id="approved-tab-content" class="hidden">
        <?php if (empty($approved_results)): ?>
            <?= placeholder_element(
                "No Approved Results",
                "Approved results will appear here once you review and approve pending submissions.",
                "fas fa-check-double"
            ) ?>
        <?php else: ?>
            <!-- Approved Results would be displayed here -->
            <p class="text-gray-600 dark:text-gray-400">Approved results list will be displayed here.</p>
        <?php endif; ?>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
function showTab(tab) {
    // Hide all tab contents
    $("#pending-tab-content, #approved-tab-content").addClass("hidden");
    $("#tab-pending, #tab-approved").removeClass("border-indigo-500 text-indigo-600").addClass("border-transparent text-gray-500");
    
    // Show selected tab content
    $("#" + tab + "-tab-content").removeClass("hidden");
    $("#tab-" + tab).removeClass("border-transparent text-gray-500").addClass("border-indigo-500 text-indigo-600 dark:text-indigo-400");
}

function viewResults(id) {
    alert_box("View results functionality for ID: " + id + ". This would open a modal/page showing all student results for review.", "info");
}

function approveResults(id) {
    if(confirm("Are you sure you want to approve these results? They will be published and visible to students.")) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "approve_results",
                result_id: id,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Results approved successfully and published", "success");
                    location.reload();
                } else {
                    if(response.errors) {
                        display_form_errors(response.errors, $('body'));
                    } else {
                        alert_box("Failed to approve results", "danger");
                    }
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
}

function rejectResults(id) {
    const reason = prompt("Please provide a reason for rejection:");
    if(reason !== null) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "reject_results",
                result_id: id,
                rejection_reason: reason,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Results rejected successfully", "success");
                    location.reload();
                } else {
                    alert_box(response.errors ? response.errors.system_error : "Failed to reject results", "danger");
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
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
