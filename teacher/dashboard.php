<?php
require_once relative_path("includes/components.php");

$title = 'Dashboard'; // Set the page title

// Example: Get logged-in teacher's name for greeting
$user = user();
$name = isset($user['othernames']) && isset($user['lastname']) ? $user['othernames'] . ' ' . $user['lastname'] : $user['username'];

// Start output buffering to capture the content
ob_start();
?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            <?php if (!empty($name)): ?>
                Welcome, <?= htmlspecialchars($name) ?>!
            <?php else: ?>
                Welcome!
            <?php endif; ?>
            
            <?php if (!empty($user['rank'])): ?>
                <span class="block mt-1 text-sm text-gray-500 dark:text-gray-400 font-normal">
                    <?= htmlspecialchars($user['rank'] . " | " . $user["qualification"]) ?>
                </span>
            <?php endif; ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
            Here is a quick overview of your teaching dashboard.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 lg:grid-cols-4">
        <!-- Card: Total Students -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Total Students</h2>
            <p class="mt-2 text-2xl font-bold text-blue-500">
                <!-- TODO: Fetch real student count for this teacher -->
                N/A
            </p>
        </div>

        <!-- Card: Total Courses -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Total Courses</h2>
            <p class="mt-2 text-2xl font-bold text-green-500">
                <!-- TODO: Fetch real course count for this teacher -->
                N/A
            </p>
        </div>

        <!-- Card: Assignments Pending -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Assignments Pending</h2>
            <p class="mt-2 text-2xl font-bold text-red-500">
                <!-- TODO: Fetch real count of assignments needing grading -->
                N/A
            </p>
        </div>

        <!-- Card: Notifications -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Notifications</h2>
            <p class="mt-2 text-2xl font-bold text-yellow-500">
                <!-- TODO: Fetch real notification count -->
                N/A
            </p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">Recent Activities</h2>
        <div class="p-6 mt-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php
                // TODO: Fetch recent activities for the logged-in teacher.
                // Example: grade submissions, post announcements, add materials, etc.
                $recent_activities = []; // Replace with database query

                if (!empty($recent_activities)):
                    foreach ($recent_activities as $activity): ?>
                        <li class="py-2 dark:text-gray-300"><?= htmlspecialchars($activity) ?></li>
                    <?php endforeach;
                else: ?>
                    <li class="py-2 dark:text-gray-300">No recent activities found.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
