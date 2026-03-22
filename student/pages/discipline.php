<?php
require_once relative_path("includes/components.php");

$title = 'Disciplinary Records'; // Set the page title
$user = user();

$idx = addslashes((string)($user['index_number'] ?? ''));
$raw = $idx !== ''
    ? fetchData('*', 'disciplinary_records', ["index_number = '{$idx}'"], 0, order_by: 'date_of_action', asc: false)
    : [];
$disciplinary_records = [];
if (is_array($raw) && !empty($raw)) {
    $list = isset($raw['id']) ? [$raw] : $raw;
    foreach ($list as $r) {
        $disciplinary_records[] = [
            'offense' => $r['offense'] ?? '',
            'action_taken' => $r['action_taken'] ?? '',
            'comments' => $r['comments'] ?? '',
            'date' => $r['date_of_action'] ?? '',
            'return_date' => $r['return_date'] ?? null,
            'status' => ((int)($r['return_status'] ?? 0) === 1) ? 'Resolved' : 'Open',
        ];
    }
}

$has_records = !empty($disciplinary_records);
$active_cases = array_filter($disciplinary_records, function($record) {
    return $record['status'] === 'Open';
});

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Summary Card -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Disciplinary Summary
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Records</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    <?= count($disciplinary_records) ?>
                </p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Cases</p>
                <p class="text-2xl font-bold <?= count($active_cases) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' ?>">
                    <?= count($active_cases) ?>
                </p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Status</p>
                <p class="text-lg font-semibold <?= count($active_cases) > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400' ?>">
                    <?= count($active_cases) > 0 ? 'Under Review' : 'Clear' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Disciplinary Records -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Disciplinary Records
        </h3>

        <?php if (!$has_records): ?>
            <?= information_bar(
                "You have no disciplinary records. Keep up the good conduct!",
                "success",
                false
            ) ?>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($disciplinary_records as $record): ?>
                    <?php
                    $status_class = $record['status'] === 'Resolved'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-yellow-100 text-yellow-800';
                    $border = $record['status'] === 'Resolved' ? 'border-green-500 bg-green-50' : 'border-amber-500 bg-amber-50';
                    ?>
                    <div class="p-4 border-l-4 <?= $border ?> rounded-lg shadow-md dark:bg-gray-800">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">
                                    <?= htmlspecialchars($record['offense']) ?>
                                </h4>
                                <span class="px-2 py-1 text-xs font-semibold rounded <?= $status_class ?>">
                                    <?= htmlspecialchars($record['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center">
                                <i class="fas fa-calendar w-4 mr-2"></i>
                                <span><strong>Date of action:</strong> <?= !empty($record['date']) ? date('F j, Y', strtotime($record['date'])) : '—' ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-gavel w-4 mr-2"></i>
                                <span><strong>Action taken:</strong> <?= htmlspecialchars($record['action_taken']) ?></span>
                            </div>
                            <?php if (!empty($record['comments'])): ?>
                                <p><strong>Comments:</strong> <?= nl2br(htmlspecialchars($record['comments'])) ?></p>
                            <?php endif; ?>
                            <?php if ($record['status'] === 'Resolved' && !empty($record['return_date'])): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle w-4 mr-2 text-green-600"></i>
                                    <span><strong>Return / closure:</strong> <?= date('F j, Y', strtotime($record['return_date'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Disciplinary Policy Information -->
    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-info-circle mr-2"></i>Disciplinary Policy
        </h3>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <p>Records reflect official actions recorded by the school. <strong>Open</strong> means the case is still active; <strong>Resolved</strong> means it has been closed or the student has returned as applicable.</p>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                For questions about your disciplinary records, please contact the Dean of Students office.
            </p>
        </div>
    </div>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
