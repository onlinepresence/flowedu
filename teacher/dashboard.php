<?php
require_once relative_path("includes/components.php");

$title = 'Dashboard'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 lg:grid-cols-4">
        <!-- Card: Total Students -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Total Students</h2>
            <p class="mt-2 text-2xl font-bold text-blue-500">N/A</p>
        </div>

        <!-- Card: Total Courses -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Total Courses</h2>
            <p class="mt-2 text-2xl font-bold text-green-500">N/A</p>
        </div>

        <!-- Card: Assignments Pending -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Assignments Pending</h2>
            <p class="mt-2 text-2xl font-bold text-red-500">N/A</p>
        </div>

        <!-- Card: Notifications -->
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Notifications</h2>
            <p class="mt-2 text-2xl font-bold text-yellow-500">N/A</p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">Recent Activities</h2>
        <div class="p-6 mt-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if(1 == 2): ?>
                <li class="py-2 dark:text-gray-300">Graded Assignment 1 for Course A</li>
                <li class="py-2 dark:text-gray-300">Uploaded new materials for Course B</li>
                <li class="py-2 dark:text-gray-300">Scheduled a meeting with Class C</li>
                <?php else: ?>
                <li class="py-2 dark:text-gray-300">No recent activities found.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>


<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
