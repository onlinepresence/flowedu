<?php
require_once relative_path("includes/components.php");

$title = 'Job Alerts'; // Set the page title
$user = user();

// Mock job alerts data - replace with actual database queries
$job_alerts = [
    [
        'id' => 1,
        'title' => 'Software Developer Intern',
        'company' => 'Tech Solutions Ghana',
        'location' => 'Accra, Ghana',
        'type' => 'Internship',
        'posted_date' => '2024-01-25',
        'deadline' => '2024-02-15',
        'status' => 'active',
        'match_score' => 85
    ],
    [
        'id' => 2,
        'title' => 'Junior Web Developer',
        'company' => 'Digital Innovations Ltd',
        'location' => 'Kumasi, Ghana',
        'type' => 'Full-time',
        'posted_date' => '2024-01-20',
        'deadline' => '2024-02-10',
        'status' => 'active',
        'match_score' => 92
    ],
    [
        'id' => 3,
        'title' => 'Database Administrator',
        'company' => 'Enterprise Systems',
        'location' => 'Accra, Ghana',
        'type' => 'Full-time',
        'posted_date' => '2024-01-18',
        'deadline' => '2024-02-05',
        'status' => 'active',
        'match_score' => 78
    ],
    [
        'id' => 4,
        'title' => 'IT Support Specialist',
        'company' => 'Global Tech Services',
        'location' => 'Remote',
        'type' => 'Part-time',
        'posted_date' => '2024-01-15',
        'deadline' => '2024-02-01',
        'status' => 'expired',
        'match_score' => 65
    ]
];

$active_jobs = array_filter($job_alerts, function($job) {
    return $job['status'] === 'active';
});

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Job Alerts Summary -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                Job Opportunities
            </h3>
            <div class="flex gap-2">
                <button 
                    onclick="refreshJobAlerts()"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100 focus:outline-none focus:shadow-outline-purple"
                >
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
                <button 
                    onclick="configureAlerts()"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"
                >
                    <i class="fas fa-cog mr-2"></i>
                    Configure Alerts
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Jobs</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    <?= count($active_jobs) ?>
                </p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Alerts</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    <?= count($job_alerts) ?>
                </p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Avg. Match Score</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    <?= count($active_jobs) > 0 ? number_format(array_sum(array_column($active_jobs, 'match_score')) / count($active_jobs), 0) : 0 ?>%
                </p>
            </div>
        </div>
    </div>

    <!-- Job Listings -->
    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            Recommended Job Opportunities
        </h3>

        <?php if (empty($active_jobs)): ?>
            <?= placeholder_element(
                "No Job Alerts Available",
                "We don't have any job opportunities matching your profile at the moment. Check back later or update your profile preferences.",
                "fas fa-briefcase"
            ) ?>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($active_jobs as $job): ?>
                    <?php
                    $type_colors = [
                        'Full-time' => 'bg-blue-100 text-blue-800',
                        'Part-time' => 'bg-green-100 text-green-800',
                        'Internship' => 'bg-purple-100 text-purple-800',
                        'Contract' => 'bg-orange-100 text-orange-800'
                    ];
                    $type_class = $type_colors[$job['type']] ?? 'bg-gray-100 text-gray-800';
                    $match_color = $job['match_score'] >= 80 ? 'text-green-600' : ($job['match_score'] >= 60 ? 'text-yellow-600' : 'text-red-600');
                    ?>
                    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hover:shadow-lg transition-shadow border-l-4 border-purple-500">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    <?= htmlspecialchars($job['title']) ?>
                                </h4>
                                <div class="flex flex-wrap items-center gap-3 mb-3">
                                    <span class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-building mr-2"></i>
                                        <?= htmlspecialchars($job['company']) ?>
                                    </span>
                                    <span class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        <?= htmlspecialchars($job['location']) ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded <?= $type_class ?>">
                                        <?= htmlspecialchars($job['type']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="mb-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Match Score</span>
                                    <p class="text-lg font-bold <?= $match_color ?>">
                                        <?= $job['match_score'] ?>%
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <span>
                                    <i class="fas fa-calendar mr-1"></i>
                                    Posted: <?= date('M j, Y', strtotime($job['posted_date'])) ?>
                                </span>
                                <span>
                                    <i class="fas fa-clock mr-1"></i>
                                    Deadline: <?= date('M j, Y', strtotime($job['deadline'])) ?>
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <button 
                                    onclick="viewJobDetails(<?= $job['id'] ?>)"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100 focus:outline-none focus:shadow-outline-purple"
                                >
                                    <i class="fas fa-eye mr-2"></i>
                                    View Details
                                </button>
                                <button 
                                    onclick="applyForJob(<?= $job['id'] ?>)"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"
                                >
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Apply Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Alert Preferences -->
    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-bell mr-2"></i>Alert Preferences
        </h3>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Configure your job alert preferences to receive notifications about opportunities that match your skills and interests.
        </p>
        <div class="flex gap-4">
            <button 
                onclick="configureAlerts()"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"
            >
                <i class="fas fa-cog mr-2"></i>
                Configure Preferences
            </button>
            <button 
                onclick="viewAllJobs()"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100 focus:outline-none focus:shadow-outline-purple"
            >
                <i class="fas fa-list mr-2"></i>
                View All Jobs
            </button>
        </div>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
    function refreshJobAlerts() {
        location.reload();
    }
    
    function configureAlerts() {
        alert('Job alert configuration will allow you to set preferences for job types, locations, and notification frequency.');
    }
    
    function viewJobDetails(jobId) {
        alert('Job details page will show full job description, requirements, and application instructions for Job ID: ' + jobId);
    }
    
    function applyForJob(jobId) {
        if (confirm('Are you sure you want to apply for this job? You will be redirected to the application page.')) {
            alert('Application functionality will redirect to the job application form for Job ID: ' + jobId);
        }
    }
    
    function viewAllJobs() {
        alert('This will show all available job opportunities, not just those matching your profile.');
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
