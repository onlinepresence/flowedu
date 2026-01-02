<?php
require_once relative_path("includes/components.php");

$title = 'Lecturer Evaluation Dashboard';
$current_tab = $tab ?? "ongoing"; // Use $_GET if $tab is not defined, ensuring robustness
$current_academic_year = getCurrentAcademicYear();

// --- 1. User & Time Context ---
$student_id = user()["id"]; // Get the authenticated student's ID
$current_time = date('Y-m-d H:i:s');

// --- 2. Tab Navigation Helper ---

/**
 * Generates the HTML for a navigation tab link.
 * @param string $tab_name The unique name of the tab (e.g., 'ongoing').
 * @param string $current_tab The currently active tab name.
 * @param string $label The display label for the tab.
 * @return string The rendered HTML link.
 */
function student_tab_link(string $tab_name, string $current_tab, string $label, $count = 0): string {
    $active_classes = 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400';
    $inactive_classes = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600';
    
    $active = ($tab_name === $current_tab) ? $active_classes : $inactive_classes;
    // Assuming the route function for the student dashboard is 'student.evaluation'
    $url = route("student.evaluation", ["tab" => $tab_name]); 

    // Use javascript:void(0) if it's the current tab to prevent unnecessary navigation
    $href = $tab_name === $current_tab ? "javascript:void(0)" : $url;

    if($count > 0){
        $label .= " ({$count})";
    }
    
    return "<a href='{$href}' class='whitespace-nowrap px-4 py-2 border-b-2 font-medium text-sm {$active}'>{$label}</a>";
}

// --- 3. Database Logic: Fetch ALL assigned evaluations and their current status ---

/**
 * Fetches all relevant evaluation forms assigned to the student, along with their response status.
 *
 * @param int $student_id The ID of the currently logged-in student.
 * @return array A list of evaluation forms with student response status included.
 */
function fetch_all_student_evaluations(int $student_id) {
    $select = [
        "EF.id", 
        "EF.title", 
        "EF.unique_code AS code", 
        "EF.start_time",
        "EF.end_time AS due_date", 
        "ER.status AS submission_status", 
        "ER.submitted_at",
        // MANDATORY ADDITION: Retrieve the current active status from the form itself
        "EF.is_active", 
    ];
    
    $table_array = [
        "join" => "evaluation_forms evaluation_responses",
        "on" => "id form_id", 
        "alias" => "EF ER",
        // Interpolate student ID directly for the additional join condition
        "add_on" => ["ER.student_id = $student_id"], 
    ];
    
    // Main WHERE clause: Filter for forms that are not globally closed (is_active > -1)
    $where = ["EF.is_active > -1"];
    
    return fetchData(
        $select, 
        $table_array, 
        $where, 
        0, 
        join_type: "LEFT", 
        order_by:"EF.end_time", 
    );
}

// Fetch all evaluation data
$all_evaluations = fetch_all_student_evaluations($student_id);

// --- 4. PHP Logic: Filter Data into Tabs based on Status and Time ---

$filtered_evaluations = [
    'ongoing' => [],
    'in_progress' => [],
    'submitted' => [],
];

foreach ($all_evaluations as $eval) {
    $is_active_now = ($eval['start_time'] <= $current_time && $eval['due_date'] >= $current_time);
    $is_upcoming = ($eval['start_time'] > $current_time);
    $is_overdue = ($eval['due_date'] < $current_time);
    
    $is_submitted = ($eval['submission_status'] === 'submitted');
    $is_draft = ($eval['submission_status'] === 'draft');
    
    // 1. Submitted (Completed)
    if ($is_submitted) {
        $filtered_evaluations['submitted'][] = $eval;
        continue;
    } 
    
    // 2. Drafted (In Progress)
    if ($is_draft && !$is_overdue) {
        $filtered_evaluations['in_progress'][] = $eval;
        continue;
    }

    // 3. Ongoing/Upcoming/Overdue (Not submitted or drafted)
    // We group all non-completed, non-drafted forms in 'ongoing' and use the loop logic to determine the exact status/action.
    if (!$is_submitted && !$is_draft) {
        $filtered_evaluations['ongoing'][] = $eval;
        continue;
    }
}

$evaluations = $filtered_evaluations[$current_tab];


// Start output buffering to capture the content
ob_start();
?>

<div class="container grid px-6 mx-auto">
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex -mb-px space-x-8" aria-label="Tabs">
            <?= student_tab_link('ongoing', $current_tab, 'Upcoming/Ongoing') ?>
            <?= student_tab_link('in_progress', $current_tab, 'In Progress/Drafts', count($filtered_evaluations["in_progress"])) ?>
            <?= student_tab_link('submitted', $current_tab, 'Submitted') ?>
        </nav>
    </div>

    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <?php if (count($evaluations) > 0): ?>
            <?= table_start() ?>
                <?= thead_start() ?>
                    <?= tr_start() ?>
                        <?= th("Evaluation Title", attribute("class", "px-4 py-3")) ?>
                        <?= th("Code", attribute("class", "px-4 py-3")) ?>
                        <?= th(($current_tab === 'submitted') ? 'Submitted Date' : 'Deadline', attribute("class", "px-4 py-3")) ?>
                        <?= th("Status", attribute("class", "px-4 py-3")) ?>
                        <?= th("Action", attribute("class", "px-4 py-3")) ?>
                    <?= tr_end() ?>
                <?= thead_end() ?>
                
                <?= tbody_start(attribute("class", "bg-white divide-y dark:divide-gray-700 dark:bg-gray-800")) ?>
                        <?php foreach ($evaluations as $evaluation): 
                            
                            // Initialize default values for Active state
                            $status = 'New / Active';
                            $badge_color = 'green';
                            $action_text = 'Start Evaluation';
                            $action_color = 'blue';
                            $date_display = date('M d, Y', strtotime($evaluation['due_date']));
                            $action_attributes = "window.location.href='" . route('student.evaluation.perform', ['code' => $evaluation['code']]) . "'"; // Default onclick action
                            $sub_text = '';

                            // Flag to determine if the button should be disabled
                            $is_disabled = false; 

                            // Cast is_active from DB to integer for reliable comparison
                            $form_active_status = (int)($evaluation['is_active'] ?? 0); 

                            switch ($current_tab) {
                                case 'ongoing':
                                    $is_active_now = ($evaluation['start_time'] <= $current_time && $evaluation['due_date'] >= $current_time);
                                    $is_upcoming = ($evaluation['start_time'] > $current_time);
                                    $is_overdue = ($evaluation['due_date'] < $current_time);
                                    
                                    if ($is_overdue) {
                                        // Case 1: Overdue
                                        
                                        // LOGIC CHANGE: Only mark as "Overdue" (Deadline Passed) if the form has been 
                                        // actively closed by the system/admin ($form_active_status == -1).
                                        if ($form_active_status == -1) {
                                            // Properly closed evaluation (Deadline Passed)
                                            $status = 'Overdue';
                                            $badge_color = 'red';
                                            $action_text = 'Deadline Passed';
                                            $action_color = 'gray';
                                            $is_disabled = true; 
                                        } else {
                                            // Overdue but was never properly closed or opened (e.g., stuck at 0 or 1).
                                            // The time is up, so it must be disabled, but we show a status that reflects a system/admin issue.
                                            $status = 'Deadline Missed'; 
                                            $badge_color = 'red';
                                            $action_text = 'Contact Admin'; // Suggest contacting admin for resolution
                                            $action_color = 'gray';
                                            $is_disabled = true; 
                                        }

                                    } elseif ($is_upcoming) {
                                        // Case 2: Upcoming (Not active, must be disabled)
                                        $status = 'Upcoming';
                                        $badge_color = 'gray';
                                        $action_text = 'Starts ' . date('M d', strtotime($evaluation['start_time']));
                                        $action_color = 'gray';
                                        $is_disabled = true;
                                        $date_display = date('M d, Y', strtotime($evaluation['due_date'])); // Still show deadline
                                        $sub_text = "Opens: " . date('M d, Y', strtotime($evaluation['start_time']));
                                    } else {
                                        // Case 3: We are in the active time window (start_time <= current_time <= due_date)
                                        if ($form_active_status === 1) {
                                            // Case 3a: Truly Active (Time window is correct AND DB status is 1)
                                            $status = 'Active';
                                            $badge_color = 'green';
                                            $action_text = 'Start Evaluation';
                                            $action_color = 'blue';
                                            // $is_disabled remains false
                                        } else {
                                            // Case 3b: Time window is correct, but DB status is 0 (Awaiting Activation by worker/admin)
                                            $status = 'Awaiting Activation';
                                            $badge_color = 'yellow';
                                            $action_text = 'Awaiting Activation';
                                            $action_color = 'gray';
                                            $is_disabled = true;
                                            $sub_text = "Opens: " . date('M d, Y H:i', strtotime($evaluation['start_time']));
                                        }
                                    }
                                    break;

                                case 'in_progress':
                                    // Item in this tab is not overdue and is a draft (must be enabled).
                                    $status = 'Draft';
                                    $badge_color = 'yellow';
                                    $action_text = 'Continue Editing';
                                    $action_color = 'purple';
                                    $sub_text = "Draft saved";
                                    break;

                                case 'submitted':
                                    // Item is completed. Action is to view results (must be enabled).
                                    $status = 'Completed';
                                    $badge_color = 'indigo';
                                    $action_text = 'View Results';
                                    $action_color = 'gray';
                                    $date_display = date('M d, Y H:i', strtotime($evaluation['submitted_at']));
                                    break;
                            }
                        ?>
                            <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
                                
                                <?= td(
                                    htmlspecialchars($evaluation['title']), 
                                    sub_text: $sub_text, 
                                    attributes: attribute("class", "px-4 py-3 text-sm font-semibold")
                                ) ?>
                                
                                <?= td(htmlspecialchars($evaluation['code']), attributes: attribute("class", "px-4 py-3 text-xs")) ?>
                                
                                <?= td($date_display, attributes: attribute("class", "px-4 py-3 text-sm")) ?>
                                
                                <?= td(td_badge($status, $badge_color), attributes: attribute("class", "px-4 py-3")) ?>
                                
                                <?= td(
                                    button(
                                        "button", 
                                        $action_text, 
                                        color: $action_color, 
                                        attributes: array_merge(
                                            attribute("class", "text-xs"),
                                            attribute("data-code", $evaluation['code']), 
                                            // Conditional check for disabling based on the $is_disabled flag
                                            attribute("disabled", $is_disabled), 
                                            // Only include the onclick event if the button is NOT disabled
                                            $is_disabled ? [] : attribute("onclick", $action_attributes)
                                        )
                                    ),
                                    attributes: attribute("class", "px-4 py-3")
                                ) ?>
                            <?= tr_end() ?>
                        <?php endforeach; ?>
                <?= tbody_end() ?>
            <?= table_end() ?>
            <?php else: ?>
                <?php 
                    $message = "No evaluations found.";
                    $icon = "fas fa-file";
                    switch ($current_tab) {
                        case 'ongoing': $message = "No active, upcoming, or overdue evaluations assigned to you."; break;
                        case 'in_progress': $message = "You have no evaluations currently saved as drafts."; break;
                        case 'submitted': $message = "You have not submitted any evaluations yet."; $icon = "fas fa-file-contract"; break;
                    }
                ?>
                <?= placeholder_element("No Evaluations Found", $message, $icon) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

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