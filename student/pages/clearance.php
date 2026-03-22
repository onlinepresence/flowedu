<?php
require_once relative_path("includes/components.php");
require_once relative_path("includes/clearance_departments.php");

$title = 'Clearance Request'; // Set the page title
$user = user();

// get final year for user program
$final_year = fetchData("program_length", "programs", "id = " . $user["program_id"])["program_length"] * 100;
$is_locked = $final_year != $user["current_year"];

$clearance_labels = clearance_department_definitions();
$clearance_status = [];

if (!$is_locked) {
    $sid = (int)($user['student_id'] ?? 0);
    $rows = fetchData('*', 'student_clearances', ["student_id = {$sid}"], 0);
    $byKey = [];
    if (is_array($rows)) {
        $list = isset($rows['id']) ? [$rows] : $rows;
        foreach ($list as $r) {
            $byKey[$r['department_key']] = $r;
        }
    }
    foreach ($clearance_labels as $key => $_label) {
        $def = default_clearance_status_for_department($key);
        if (isset($byKey[$key])) {
            $row = $byKey[$key];
            $clearedLabel = null;
            if (!empty($row['cleared_by'])) {
                $u = fetchData('username', 'users', ['id' => (int)$row['cleared_by']], 1);
                $clearedLabel = is_array($u) ? ($u['username'] ?? null) : null;
            }
            $clearance_status[$key] = [
                'status' => $row['status'],
                'cleared_by' => $clearedLabel,
                'cleared_at' => $row['cleared_at'] ?? null,
            ];
        } else {
            $clearance_status[$key] = [
                'status' => $def,
                'cleared_by' => null,
                'cleared_at' => null,
            ];
        }
    }
}

// Calculate overall status only if clearance is not locked
$overall_status = 'locked'; // pending, cleared, partial, locked
$cleared_count = 0;
$total_count = 0;

if (!$is_locked) {
    foreach ($clearance_status as $status) {
        // Only count departments that require clearance (not 'not_required')
        if ($status['status'] !== 'not_required') {
            $total_count++;
            if ($status['status'] === 'cleared') {
                $cleared_count++;
            }
        }
    }
    
    // Determine overall status
    if ($total_count > 0) {
        if ($cleared_count === $total_count) {
            $overall_status = 'cleared';
        } elseif ($cleared_count > 0) {
            $overall_status = 'partial';
        } else {
            $overall_status = 'pending';
        }
    }
}

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <?php if ($is_locked): ?>
        <!-- Locked Status Message -->
        <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <div class="text-center py-8">
                <i class="fas fa-lock text-6xl text-gray-400 dark:text-gray-500 mb-4"></i>
                <h3 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                    Clearance Not Available
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-2xl mx-auto">
                    Clearance is only available for final year students (Level <?= $final_year ?>). 
                    You are currently in Level <?= htmlspecialchars($user['current_year'] ?? 'N/A') ?>.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Please complete your studies and reach your final year to access clearance services.
                </p>
            </div>
        </div>
    <?php else: ?>
    <!-- Overall Status Card -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                Clearance Status
            </h3>
            <?php
            $status_colors = [
                'cleared' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
                'partial' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-clock'],
                'pending' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-exclamation-circle']
            ];
            $status_info = $status_colors[$overall_status] ?? $status_colors['pending'];
            ?>
            <span class="px-4 py-2 text-sm font-semibold rounded-full <?= $status_info['bg'] ?> <?= $status_info['text'] ?>">
                <i class="fas <?= $status_info['icon'] ?> mr-2"></i>
                <?= ucfirst($overall_status) ?>
            </span>
        </div>
        
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Progress</span>
                <span><?= $cleared_count ?> of <?= $total_count ?> departments cleared</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div 
                    class="bg-purple-600 h-2.5 rounded-full transition-all duration-300" 
                    style="width: <?= $total_count > 0 ? ($cleared_count / $total_count * 100) : 0 ?>%"
                ></div>
            </div>
        </div>
    </div>

    <!-- Clearance Details -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Department Clearance Details
        </h3>
        
        <div class="grid gap-4 md:grid-cols-2">
            <?php foreach ($clearance_status as $key => $status): ?>
                <?php if ($status['status'] === 'not_required') continue; ?>
                
                <?php
                $status_colors = [
                    'cleared' => ['border' => 'border-green-500', 'bg' => 'bg-green-50', 'icon' => 'fa-check-circle', 'text' => 'text-green-700'],
                    'pending' => ['border' => 'border-yellow-500', 'bg' => 'bg-yellow-50', 'icon' => 'fa-clock', 'text' => 'text-yellow-700']
                ];
                $dept_info = $status_colors[$status['status']] ?? $status_colors['pending'];
                ?>
                
                <div class="p-4 border-2 <?= $dept_info['border'] ?> <?= $dept_info['bg'] ?> rounded-lg dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                            <?= $clearance_labels[$key] ?>
                        </h4>
                        <i class="fas <?= $dept_info['icon'] ?> <?= $dept_info['text'] ?>"></i>
                    </div>
                    
                    <?php if ($status['status'] === 'cleared' && !empty($status['cleared_at'])): ?>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <?php if (!empty($status['cleared_by'])): ?>
                                <p><strong>Cleared by:</strong> <?= htmlspecialchars((string)$status['cleared_by']) ?></p>
                            <?php endif; ?>
                            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($status['cleared_at'])) ?></p>
                        </div>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Pending clearance. Please visit the <?= $clearance_labels[$key] ?> to complete your clearance.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Request Clearance Button -->
    <?php if ($overall_status !== 'cleared'): ?>
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                Request Clearance
            </h3>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                If you believe you have completed all requirements but your clearance is still pending, you can submit a clearance request.
            </p>
            <form action="<?= url('student/submit.php') ?>" method="POST">
                <?= input("hidden", "", "request_type", "clearance_request") ?>
                <?= textarea("request_notes", "Additional Notes (Optional)", "", false, attribute("rows", "4")) ?>
                <div class="mt-4">
                    <?= button("submit", "Submit Clearance Request", "submit", "request_clearance", "purple") ?>
                </div>
            </form>
        </div>
    <?php else: ?>
        <?= information_bar(
            "Congratulations! You have completed all clearance requirements. You can now proceed with your academic activities.",
            "success",
            false
        ) ?>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
