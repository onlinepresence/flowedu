<?php

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
 * The main dispatcher function called by worker.php to run evaluation maintenance.
 * This is where you queue up all your evaluation-related automatic tasks.
 */
function run_automatic_jobs() {
    // Run the core function for updating active/closed statuses
    run_automatic_job('process_evaluation_status_updates');
    
    // You can add more automatic evaluation jobs here in the future
    // run_automatic_job('send_deadline_reminders');
}