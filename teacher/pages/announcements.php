<?php
require_once relative_path("includes/components.php");

$title = 'Announcements'; // Set the page title
$user = user();

// Mock announcements data - replace with actual database queries
$announcements_data = [
    [
        'title' => 'Assignment 1 Deadline Extension',
        'course_code' => 'CS 101',
        'message' => 'The deadline for Assignment 1 has been extended to January 30, 2025.',
        'created_date' => '2025-01-20',
        'status' => 'active'
    ],
    [
        'title' => 'Class Cancellation Notice',
        'course_code' => 'CS 301',
        'message' => 'The class scheduled for January 25, 2025 is cancelled. Please check your email for rescheduling details.',
        'created_date' => '2025-01-22',
        'status' => 'active'
    ]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Create and manage announcements for your students
            </p>
        </div>
        <?= button("button", "New Announcement", attributes: array_merge(
            attribute("id", "new-announcement-btn"),
            attribute("class", "max-w-xs")
        )) ?>
    </div>

    <?php if (empty($announcements_data)): ?>
        <?= placeholder_element(
            "No Announcements",
            "You haven't created any announcements yet. Click 'New Announcement' to create one.",
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
                                <?= htmlspecialchars($announcement['course_code']) ?> • <?= date('M d, Y', strtotime($announcement['created_date'])) ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= 
                            $announcement['status'] === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                            'bg-gray-100 text-gray-800' 
                        ?>">
                            <?= ucfirst($announcement['status']) ?>
                        </span>
                    </div>
                    
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        <?= htmlspecialchars($announcement['message']) ?>
                    </p>
                    
                    <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button class="px-3 py-1 text-sm text-red-600 hover:text-red-800 dark:text-red-400">
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
    $("#new-announcement-btn").click(function(){
        alert_box("Announcement creation modal will be implemented", "info");
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
