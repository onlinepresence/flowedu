<?php
require_once relative_path("includes/components.php");

$title = 'Lecturer Evaluation Dashboard';
$current_tab = $_GET['tab'] ?? 'ongoing'; // Default to ongoing

// --- 1. Tab Navigation Helper ---

/**
 * Generates the HTML for a navigation tab link.
 * @param string $tab_name The unique name of the tab (e.g., 'ongoing').
 * @param string $current_tab The currently active tab name.
 * @param string $label The display label for the tab.
 * @return string The rendered HTML link.
 */
function student_tab_link(string $tab_name, string $current_tab, string $label): string {
    $active_classes = 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400';
    $inactive_classes = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600';
    
    $active = ($tab_name === $current_tab) ? $active_classes : $inactive_classes;
    // Assuming the route function for the student dashboard is 'student.evaluation'
    $url = route("student.evaluation", ["tab" => $tab_name]); 

    // Use javascript:void(0) if it's the current tab to prevent unnecessary navigation
    $href = $tab_name === $current_tab ? "javascript:void(0)" : $url;
    
    return "<a href='{$href}' class='whitespace-nowrap px-4 py-2 border-b-2 font-medium text-sm {$active}'>{$label}</a>";
}

// --- 2. Dummy Data Fetching (Replace with actual DB queries) ---

// Dummy student ID (replace with actual session user ID)
$student_id = user()["id"];

/**
 * Simulates fetching evaluation forms based on student's status.
 * @param string $type 'ongoing', 'in_progress', 'submitted'
 * @return array
 */
function fetch_evaluations(string $type, int $student_id): array {
    $data = [
        'ongoing' => [
            ['id' => 1, 'title' => 'Semester 1 - Calculus I Evaluation', 'code' => 'CALC-S1-25', 'due_date' => '2025-12-15 23:59:59', 'status' => 'New'],
            ['id' => 2, 'title' => 'Introduction to Programming Evaluation', 'code' => 'PROG-S1-25', 'due_date' => '2025-12-20 23:59:59', 'status' => 'Ongoing'],
        ],
        'in_progress' => [
            ['id' => 3, 'title' => 'Networking Fundamentals Survey', 'code' => 'NET-S1-25', 'due_date' => '2025-12-18 23:59:59', 'status' => 'Draft', 'progress' => '4/10 Questions'],
        ],
        'submitted' => [
            ['id' => 4, 'title' => 'Database Systems Feedback', 'code' => 'DB-S1-25', 'submitted_date' => '2025-11-25 10:30:00', 'status' => 'Completed'],
        ],
    ];
    return $data[$type] ?? [];
}

$evaluations = fetch_evaluations($current_tab, $student_id);

// Start output buffering to capture the content
ob_start();
?>

<div class="container grid px-6 mx-auto">
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex -mb-px space-x-8" aria-label="Tabs">
            <?= student_tab_link('ongoing', $current_tab, 'Upcoming/Ongoing') ?>
            <?= student_tab_link('in_progress', $current_tab, 'In Progress') ?>
            <?= student_tab_link('submitted', $current_tab, 'Submitted') ?>
        </nav>
    </div>

    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <?= table_start() ?>
                <?= thead_start() ?>
                    <?= tr_start() ?>
                    <!-- <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800"> -->
                        <?= th("Evaluation Title", attribute("class", "px-4 py-3")) ?>
                        <?= th("Code", attribute("class", "px-4 py-3")) ?>
                        <?= th(($current_tab === 'submitted') ? 'Submitted Date' : 'Deadline') ?>
                        <?= th("Status", attribute("class", "px-4 py-3")) ?>
                        <?= th("Action", attribute("class", "px-4 py-3")) ?>
                    <?= tr_end() ?>
                <?= thead_end() ?>
                <!-- <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800"> -->
                <?= tbody_start() ?>
                    <?php if (count($evaluations) > 0): ?>
                        <?php foreach ($evaluations as $evaluation): 
                            
                            $status = 'N/A';
                            $badge_color = 'gray';
                            $action_text = 'View';
                            $action_color = 'gray';
                            $date_display = '';

                            switch ($current_tab) {
                                case 'ongoing':
                                    $status = 'New';
                                    $badge_color = 'green';
                                    $action_text = 'Start Evaluation';
                                    $action_color = 'blue';
                                    $date_display = date('M d, Y', strtotime($evaluation['due_date']));
                                    break;
                                case 'in_progress':
                                    $status = 'Draft';
                                    $badge_color = 'yellow';
                                    $action_text = 'Continue Editing';
                                    $action_color = 'purple';
                                    $date_display = date('M d, Y', strtotime($evaluation['due_date']));
                                    break;
                                case 'submitted':
                                    $status = 'Completed';
                                    $badge_color = 'red';
                                    $action_text = 'View Results';
                                    $action_color = 'gray';
                                    $date_display = date('M d, Y H:i', strtotime($evaluation['submitted_at']));
                                    break;
                            }
                        ?>
                            <!-- <tr class="text-gray-700 dark:text-gray-400"> -->
                            <?= tr_start() ?>
                                <?= td(
                                    htmlspecialchars($evaluation['title']), 
                                    sub_text: ($current_tab === 'in_progress' ? "Status: $status" : ""), 
                                    attributes: attribute("class", "px-4 py-3 text-sm font-semibold")
                                ) ?>
                                <?= td(htmlspecialchars($evaluation['code']), attributes: attribute("class", "px-4 py-3 text-xs")) ?>
                                <?= td($date_display, attributes: attribute("class", "px-4 py-3 text-sm")) ?>
                                <?= td_badge($status, $badge_color) ?>
                                <?= td(
                                    button(
                                        "button", 
                                        $action_text, 
                                        color: $action_color, 
                                        attributes: array_merge(
                                            attribute("class", "text-xs"),
                                            attribute("onclick", "window.location.href='" . route('student.evaluation.perform', ['code' => $evaluation['code']]) . "'")
                                        )
                                    ),
                                    attributes: attribute("class", "px-4 py-3")
                                ) ?>
                            <?= tr_end() ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php 
                            $message = "No evaluations found.";
                            switch ($current_tab) {
                                case 'ongoing': $message = "No ongoing or upcoming evaluations assigned to you."; break;
                                case 'in_progress': $message = "You have no evaluations currently saved as drafts."; break;
                                case 'submitted': $message = "You have not submitted any evaluations yet."; break;
                            }
                        ?>
                        <?= td_empty($message, 5) ?>
                    <?php endif; ?>
                <?= tbody_end() ?>
            <?= table_end() ?>
        </div>
    </div>
</div>

<?php 
// Placeholder for a detailed evaluation page route (student/evaluation/perform.php)
// We will need to create this next.
?>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Any client-side logic specific to the student dashboard
    });
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');