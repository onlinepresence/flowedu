<?php

require_once __DIR__ . '/settings_functions.php';

/**
 * Executes a single automatic, non-queued job, handling exceptions and logging.
 *
 * This function checks for function validity, executes it with provided parameters, 
 * catches exceptions, and logs the result.
 *
 * @param string $function_name The name of the function to execute (e.g., 'process_evaluation_status_updates').
 * @param array $parameters Optional array of parameters for the function.
 * @return bool True if the job succeeded and the function returned true, false otherwise.
 */
function run_automatic_job(string $function_name, array $parameters = []): bool {
    global $last_exception; // Used for capturing exception messages for logging

    if (!function_exists($function_name)) {
        log_cron_message("Automatic Job Error: Function '$function_name' does not exist.", "error");
        return false;
    }

    $success = false;
    try {
        // Execute the function with arguments
        $response = call_user_func_array($function_name, $parameters);

        if ($response === true) {
            log_cron_message("Automatic Job: '$function_name' finished successfully.");
            $success = true;
        } else {
            // Function executed but returned false, indicating a functional error (e.g., failed DB query)
            log_cron_message("Automatic Job Error: Function '$function_name' failed functionally (returned false).", "error");
        }
    } catch (Exception $e) {
        // Handle runtime exceptions
        $last_exception = $e->getMessage();
        log_cron_message("Automatic Job Exception in '$function_name': " . $last_exception, "critical");
        // Reset last_exception after logging, matching the pattern in process_job
        $last_exception = null;
    }

    return $success;
}

/**
 * Maintenance function: Processes automatic updates to evaluation form statuses.
 * This is the core logic function that runs the required database updates.
 * * @return bool True if the database queries executed without throwing an error (regardless of how many rows were affected).
 */
function process_evaluation_status_updates(): bool {
    global $connect;
    
    // 1. Close Overdue Evaluations (Set is_active to -1)
    // Only close forms that are currently active (is_active = 1 or 0) and past their end_time.
    $closed_query = "UPDATE evaluation_forms 
                     SET is_active = -1 
                     WHERE end_time < NOW() AND is_active != -1";
    $closed_count = $connect->query($closed_query);

    // 2. Open Upcoming Evaluations (Set is_active to 1)
    // Only open forms that are currently between start_time and end_time, and not already submitted/active/closed.
    $open_query = "UPDATE evaluation_forms 
                   SET is_active = 1 
                   WHERE start_time <= NOW() 
                     AND end_time >= NOW() 
                     AND is_active != 1";
    $open_count = $connect->query($open_query);

    // Check if both queries succeeded (db_query returns false on failure)
    if ($closed_count !== false && $open_count !== false) {
        log_cron_message("Evaluation Status Update: Closed {$closed_count} forms, Opened {$open_count} forms.");
        return true;
    }
    
    // If a database query failed, return false to signal an issue
    return false;
}

/**
 * Maintenance function: Sets semester is_active from calendar dates (today in app timezone).
 * Active when start_date <= today <= end_date; otherwise inactive. Does not change academic_sessions.is_current.
 *
 * @return bool True if both update queries succeeded.
 */
function process_session_status_updates(): bool {
    global $connect;

    $today = $connect->real_escape_string(date('Y-m-d'));

    $deactivate = "UPDATE semesters 
                   SET is_active = 0 
                   WHERE is_active != 0 
                     AND (
                       start_date IS NULL 
                       OR end_date IS NULL 
                       OR start_date > '{$today}' 
                       OR end_date < '{$today}'
                     )";

    $activate = "UPDATE semesters 
                 SET is_active = 1 
                 WHERE start_date IS NOT NULL 
                   AND end_date IS NOT NULL 
                   AND start_date <= '{$today}' 
                   AND end_date >= '{$today}' 
                   AND is_active != 1";

    $off = $connect->query($deactivate);
    $on = $connect->query($activate);

    if ($off !== false && $on !== false) {
        log_cron_message("Semester active status updated for date {$today}.");
        return true;
    }

    return false;
}

/**
 * The main dispatcher function called by worker.php to run evaluation maintenance.
 * This is where you queue up all your evaluation-related automatic tasks.
 */
function process_auto_promotion(): bool
{
    if (get_setting('students.promotion_mode', 'auto') !== 'auto') {
        return true;
    }

    $ctx = get_academic_sessions(limit: 2);

    if(!is_array($ctx) || count($ctx) < 2){
        log_cron_message('Auto promotion skipped: academic sessions not reached');
        return false;
    }

    $ctx = current_session_and_semester();
    $session = $ctx['session'] ?? [];
    
    if (empty($session['id'])) {
        log_cron_message('Auto promotion skipped: no current academic session.');
        return true;
    }else if($session['end_date'] < date("Y-m-d")){
        log_cron_message('Auto promotion skipped: current academic session is past.');
        return true;
    }

    $sessionId = (int)$session['id'];
    $tables = [
        ['join' => 'students programs', 'on' => 'program_id id', 'alias' => 's p'],
        ['join' => 'students promotions', 'on' => 'id student_id', 'alias' => 's pr']
    ];
    $columns = ['s.id', 's.current_year', 'p.program_length'];
    $where = ['s.approved = 1', 's.graduated = 0', 's.program_id IS NOT NULL', 'p.program_length IS NOT NULL', 'pr.academic_session_id IS NULL'];
    $rows = fetchData($columns, $tables, $where, 0, join_type: "left");

    formatDump($rows);exit;
    if (!is_array($rows)) {
        return false;
    }
    if (isset($rows['id'])) {
        $rows = [$rows];
    }

    foreach ($rows as $row) {
        $cy = (int)$row['current_year'];
        $maxYear = (int)$row['program_length'] * 100;
        if ($cy <= 0 || $cy >= $maxYear) {
            continue;
        }
        $toLevel = $cy + 100;
        $dup = fetchData('id', 'promotions', [
            'student_id = ' . (int)$row['id'],
            'academic_session_id = ' . $sessionId,
            'from_level = ' . $cy,
            'to_level = ' . $toLevel,
        ], 1, where_binds: 'AND');
        if (!empty($dup['id'])) {
            continue;
        }

        $promo = [
            'student_id' => (int)$row['id'],
            'from_level' => $cy,
            'to_level' => $toLevel,
            'academic_session_id' => $sessionId,
            'promoted_by' => null,
            'promotion_date' => date('Y-m-d'),
        ];
        if (data_insert('promotions', $promo)) {
            update(['id' => (int)$row['id']], ['current_year' => (string)$toLevel], 'students', ['id']);
        }
    }

    return true;
}

function run_automatic_jobs() {
    // Run the core function for updating active/closed statuses
    run_automatic_job('process_evaluation_status_updates');

    run_automatic_job('process_session_status_updates');

    // run on the fifteenth of every month
    if(date("d") == 15){
        if (get_setting('students.promotion_mode', 'auto') === 'auto') {
            run_automatic_job('process_auto_promotion');
        }
    }
    
    
    // You can add more automatic evaluation jobs here in the future
    // run_automatic_job('send_deadline_reminders');
}