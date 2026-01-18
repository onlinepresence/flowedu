<?php
require_once relative_path("includes/components.php");

$title = 'Teacher Announcements'; // Set the page title
$page_title = 'Teacher Announcements Management';

// Mock announcements data - replace with actual database queries
$announcements_data = [
    [
        'id' => 1,
        'title' => 'Assignment 1 Deadline Extension',
        'course_code' => 'CS 101',
        'course_name' => 'Introduction to Computer Science',
        'teacher_name' => 'Dr. John Smith',
        'message' => 'The deadline for Assignment 1 has been extended to January 30, 2025.',
        'created_date' => '2025-01-20',
        'status' => 'active',
        'published' => true
    ],
    [
        'id' => 2,
        'title' => 'Class Cancellation Notice',
        'course_code' => 'CS 301',
        'course_name' => 'Database Systems',
        'teacher_name' => 'Prof. Jane Doe',
        'message' => 'The class scheduled for January 25, 2025 is cancelled. Please check your email for rescheduling details.',
        'created_date' => '2025-01-22',
        'status' => 'pending',
        'published' => false
    ]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Review and manage announcements created by teachers
        </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Status
                </label>
                <select id="status-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Teacher
                </label>
                <select id="teacher-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Teachers</option>
                    <option value="Dr. John Smith">Dr. John Smith</option>
                    <option value="Prof. Jane Doe">Prof. Jane Doe</option>
                </select>
            </div>
            <div class="flex items-end">
                <?= button("button", "Reset Filters", attributes: array_merge(
                    attribute("id", "reset-filters"),
                    attribute("class", "w-full")
                )) ?>
            </div>
        </div>
    </div>

    <?php if (empty($announcements_data)): ?>
        <?= placeholder_element(
            "No Announcements",
            "No announcements have been created by teachers yet.",
            "fas fa-bullhorn"
        ) ?>
    <?php else: ?>
        <!-- Announcements List -->
        <div class="grid gap-6">
            <?php foreach ($announcements_data as $announcement): ?>
                <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                <?= htmlspecialchars($announcement['title']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <span class="font-medium"><?= htmlspecialchars($announcement['course_code']) ?> - <?= htmlspecialchars($announcement['course_name']) ?></span>
                                • Teacher: <?= htmlspecialchars($announcement['teacher_name']) ?>
                                • <?= date('M d, Y', strtotime($announcement['created_date'])) ?>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= 
                                $announcement['status'] === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                ($announcement['status'] === 'archived' ? 'bg-gray-100 text-gray-800' : 
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') 
                            ?>">
                                <?= ucfirst($announcement['status']) ?>
                            </span>
                            <?php if (!$announcement['published']): ?>
                                <span class="px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                    Unpublished
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        <?= htmlspecialchars($announcement['message']) ?>
                    </p>
                    
                    <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <?php if ($announcement['status'] === 'pending'): ?>
                            <button onclick="approveAnnouncement(<?= $announcement['id'] ?>)" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                <i class="fas fa-check mr-1"></i>Approve & Publish
                            </button>
                            <button onclick="rejectAnnouncement(<?= $announcement['id'] ?>)" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        <?php endif; ?>
                        <button onclick="editAnnouncement(<?= $announcement['id'] ?>)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button onclick="archiveAnnouncement(<?= $announcement['id'] ?>)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                            <i class="fas fa-archive mr-1"></i>Archive
                        </button>
                        <button onclick="deleteAnnouncement(<?= $announcement['id'] ?>)" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    $("#status-filter, #teacher-filter").change(function(){
        // Filter logic would be implemented here
        alert_box("Filter functionality will be implemented", "info");
    });
    
    $("#reset-filters").click(function(){
        $("#status-filter, #teacher-filter").val("");
    });
});

function approveAnnouncement(id) {
    $.ajax({
        url: relative_path("admin/submit.php"),
        method: "POST",
        data: {
            submit: "approve_announcement",
            announcement_id: id,
            response_type: "json"
        },
        success: function(response) {
            if(response.status) {
                alert_box(response.data.message || "Announcement approved and published successfully", "success");
                location.reload();
            } else {
                if(response.errors) {
                    display_form_errors(response.errors, $('body'));
                } else {
                    alert_box("Failed to approve announcement", "danger");
                }
            }
        },
        error: function() {
            alert_box("System error occurred", "danger");
        }
    });
}

function rejectAnnouncement(id) {
    const reason = prompt("Please provide a reason for rejection:");
    if(reason !== null) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "reject_announcement",
                announcement_id: id,
                rejection_reason: reason,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Announcement rejected successfully", "success");
                    location.reload();
                } else {
                    alert_box(response.errors ? response.errors.system_error : "Failed to reject announcement", "danger");
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
}

function editAnnouncement(id) {
    alert_box("Edit functionality for announcement ID: " + id, "info");
}

function archiveAnnouncement(id) {
    if(confirm("Are you sure you want to archive this announcement?")) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "archive_announcement",
                announcement_id: id,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Announcement archived successfully", "success");
                    location.reload();
                } else {
                    alert_box(response.errors ? response.errors.system_error : "Failed to archive announcement", "danger");
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
}

function deleteAnnouncement(id) {
    if(confirm("Are you sure you want to delete this announcement? This action cannot be undone.")) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "delete_announcement",
                announcement_id: id,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Announcement deleted successfully", "success");
                    location.reload();
                } else {
                    alert_box(response.errors ? response.errors.system_error : "Failed to delete announcement", "danger");
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
