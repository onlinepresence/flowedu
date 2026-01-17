<?php
require_once relative_path("includes/components.php");

$title = 'Disciplinary Records'; // Set the page title
$user = user();

// Mock disciplinary records - replace with actual database queries
$disciplinary_records = [
    [
        'date' => '2024-01-10',
        'incident' => 'Late submission of assignment',
        'violation_type' => 'Academic',
        'severity' => 'Minor',
        'action_taken' => 'Warning issued',
        'status' => 'Resolved',
        'resolved_date' => '2024-01-12'
    ],
    [
        'date' => '2023-11-15',
        'incident' => 'Disruptive behavior in class',
        'violation_type' => 'Behavioral',
        'severity' => 'Moderate',
        'action_taken' => 'Counseling session required',
        'status' => 'Resolved',
        'resolved_date' => '2023-11-20'
    ]
];

$has_records = !empty($disciplinary_records);
$active_cases = array_filter($disciplinary_records, function($record) {
    return $record['status'] === 'Active';
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
                    $severity_colors = [
                        'Minor' => 'border-yellow-500 bg-yellow-50',
                        'Moderate' => 'border-orange-500 bg-orange-50',
                        'Major' => 'border-red-500 bg-red-50'
                    ];
                    $severity_class = $severity_colors[$record['severity']] ?? 'border-gray-500 bg-gray-50';
                    $status_colors = [
                        'Active' => 'bg-red-100 text-red-800',
                        'Resolved' => 'bg-green-100 text-green-800',
                        'Pending' => 'bg-yellow-100 text-yellow-800'
                    ];
                    $status_class = $status_colors[$record['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <div class="p-4 border-l-4 <?= $severity_class ?> rounded-lg shadow-md dark:bg-gray-800">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">
                                    <?= htmlspecialchars($record['incident']) ?>
                                </h4>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded">
                                        <?= htmlspecialchars($record['violation_type']) ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded">
                                        <?= htmlspecialchars($record['severity']) ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded <?= $status_class ?>">
                                        <?= htmlspecialchars($record['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center">
                                <i class="fas fa-calendar w-4 mr-2"></i>
                                <span><strong>Date:</strong> <?= date('F j, Y', strtotime($record['date'])) ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-gavel w-4 mr-2"></i>
                                <span><strong>Action Taken:</strong> <?= htmlspecialchars($record['action_taken']) ?></span>
                            </div>
                            <?php if ($record['status'] === 'Resolved' && isset($record['resolved_date'])): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle w-4 mr-2 text-green-600"></i>
                                    <span><strong>Resolved:</strong> <?= date('F j, Y', strtotime($record['resolved_date'])) ?></span>
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
            <p><strong>Minor Violations:</strong> Warnings and counseling sessions. Usually resolved within 1-2 weeks.</p>
            <p><strong>Moderate Violations:</strong> May require formal meetings, additional requirements, or probation. Resolution typically takes 2-4 weeks.</p>
            <p><strong>Major Violations:</strong> May result in suspension, expulsion, or other serious consequences. Requires formal disciplinary hearing.</p>
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
