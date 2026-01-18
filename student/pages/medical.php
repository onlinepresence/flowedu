<?php
require_once relative_path("includes/components.php");

$title = 'Medical Information'; // Set the page title
$user = user();

// Mock medical data - replace with actual database queries
$medical_info = [
    'insurance_number' => $user['insurance_number'] ?? $user['ghana_card'],
    'blood_type' => 'O+',
    'allergies' => $user['allergy'] ?? 'None recorded',
    'chronic_conditions' => 'None',
    'emergency_contact' => [
        'name' => 'John Doe',
        'relationship' => 'Parent',
        'phone' => '+233 24 123 4567'
    ],
    'medical_records' => [
        [
            'date' => '2024-01-15',
            'type' => 'Check-up',
            'description' => 'Routine medical check-up',
            'doctor' => 'Dr. Sarah Mensah',
            'status' => 'completed'
        ],
        [
            'date' => '2024-02-20',
            'type' => 'Vaccination',
            'description' => 'COVID-19 Booster',
            'doctor' => 'Dr. Sarah Mensah',
            'status' => 'completed'
        ]
    ]
];

$medical_info["medical_records"] = [];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Medical Information Card -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-user-md mr-2"></i>Personal Medical Information
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Insurance Number
                    </label>
                    <p class="text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($medical_info['insurance_number']) ?>
                    </p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Blood Type
                    </label>
                    <p class="text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($medical_info['blood_type']) ?>
                    </p>
                </div>
            </div>
            
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Known Allergies
                    </label>
                    <p class="text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($medical_info['allergies']) ?>
                    </p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Chronic Conditions
                    </label>
                    <p class="text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($medical_info['chronic_conditions']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-phone-alt mr-2"></i>Emergency Contact
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Name
                </label>
                <p class="text-gray-800 dark:text-gray-200">
                    <?= htmlspecialchars($medical_info['emergency_contact']['name']) ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Relationship
                </label>
                <p class="text-gray-800 dark:text-gray-200">
                    <?= htmlspecialchars($medical_info['emergency_contact']['relationship']) ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Phone Number
                </label>
                <p class="text-gray-800 dark:text-gray-200">
                    <?= htmlspecialchars($medical_info['emergency_contact']['phone']) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Medical Records History -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-file-medical mr-2"></i>Medical Records History
        </h3>

        <?php if (empty($medical_info['medical_records'])): ?>
            <?= placeholder_element(
                "No Medical Records",
                "Your medical records will appear here once you visit the medical center.",
                "fas fa-file-medical"
            ) ?>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($medical_info['medical_records'] as $record): ?>
                    <div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800 border-l-4 border-purple-500">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                    <?= htmlspecialchars($record['type']) ?>
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars($record['description']) ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">
                                <?= ucfirst($record['status']) ?>
                            </span>
                        </div>
                        <div class="mt-3 flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-calendar mr-2"></i>
                            <span><?= date('F j, Y', strtotime($record['date'])) ?></span>
                            <i class="fas fa-user-md ml-4 mr-2"></i>
                            <span><?= htmlspecialchars($record['doctor']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Update Medical Information -->
    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Update Medical Information
        </h3>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            To update your medical information, please visit the medical center or contact the health services office.
        </p>
        <div class="flex gap-4">
            <a 
                href="mailto:medical@school.edu"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"
            >
                <i class="fas fa-envelope mr-2"></i>
                Contact Medical Center
            </a>
            <button 
                onclick="requestMedicalUpdate()"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:shadow-outline-blue"
            >
                <i class="fas fa-edit mr-2"></i>
                Request Update
            </button>
        </div>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
    function requestMedicalUpdate() {
        alert('Medical information update requests should be submitted through the medical center. Please visit in person or contact them via email.');
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
