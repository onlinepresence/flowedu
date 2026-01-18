<?php
require_once relative_path("includes/components.php");

$title = 'Messages'; // Set the page title
$user = user();

// Mock messages data - replace with actual database queries
$messages_data = [
    [
        'from' => 'John Doe',
        'index_number' => 'CS/2020/001',
        'subject' => 'Question about Assignment 1',
        'message' => 'I have a question regarding the requirements for Assignment 1...',
        'date' => '2025-01-20 14:30',
        'read' => false
    ],
    [
        'from' => 'Jane Smith',
        'index_number' => 'CS/2020/002',
        'subject' => 'Request for Extension',
        'message' => 'I would like to request an extension for the upcoming assignment due to...',
        'date' => '2025-01-19 10:15',
        'read' => true
    ],
    [
        'from' => 'Michael Brown',
        'index_number' => 'CS/2020/045',
        'subject' => 'Class Material Access',
        'message' => 'I am having trouble accessing the class materials uploaded last week...',
        'date' => '2025-01-18 16:45',
        'read' => true
    ]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6">
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            View and respond to messages from students
        </p>
    </div>

    <?php if (empty($messages_data)): ?>
        <?= placeholder_element(
            "No Messages",
            "You don't have any messages yet. Messages from students will appear here.",
            "fas fa-inbox"
        ) ?>
    <?php else: ?>
        <!-- Messages List -->
        <div class="space-y-4">
            <?php foreach ($messages_data as $message): ?>
                <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 <?= !$message['read'] ? 'border-l-4 border-blue-500' : '' ?>">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                    <?= htmlspecialchars($message['subject']) ?>
                                </h3>
                                <?php if (!$message['read']): ?>
                                    <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">
                                        New
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                From: <span class="font-medium"><?= htmlspecialchars($message['from']) ?></span> 
                                (<?= htmlspecialchars($message['index_number']) ?>)
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                <?= $message['date'] ?>
                            </p>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        <?= htmlspecialchars($message['message']) ?>
                    </p>
                    
                    <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-reply mr-1"></i>Reply
                        </button>
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                            <i class="fas fa-eye mr-1"></i>View Full
                        </button>
                        <button class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
