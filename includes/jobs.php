<?php

    /**
     * This is specifically used for the cron worker only
     * @param string $message The message to be displayed
     */
    function log_cron_message($message) {
        global $rootPath;

        // Define log directory path by month and year
        $logDir = $rootPath . '/logs/cron/' . date('m_Y');

        // Create the directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        // Define log file path (per day or task to keep files small)
        $logFile = $logDir . '/worker_' . date('Y-m-d') . '.log';

        // Create a formatted message
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;

        // Append to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    /**
     * This is used to add a new job to the jobs table
     * @param string $queue The type of queue this job belongs to
     * @param array $payload The payload
     * @param int $delay Adds a delay to when the job should be run in seconds
     * @return bool
     */
    function add_job(string $queue, array $payload, int $delay = 0) :bool {
        $time = time() + $delay;
        $data = [
            "queue" => $queue, 
            "payload" => serialize_(json_encode($payload)), 
            "attempts" => 0,
            "available_at" => $time,
            "created_at" => $time
        ];

        return data_insert("jobs", $data);
    }

    /**
     * This is used to extract certain keys to be used for a payload during jobs
     * @param array $data The data to be made into a payload
     * @param array $extract The list of keys to be extracted as the final payload
     * @return array
     */
    function create_arguments(array $data, array $extract) :array{
        $response = $data;

        if($data && $extract){
            foreach($data as $key => $value){
                if(!in_array($key, $extract)){
                    unset($response[$key]);
                }
            }
        }

        return $response;
    }

    /**
     * Create payload. Returns false if the function name is not found
     * @param string $function_name The name of the function to be used for this queue
     * @param array $arguments The arguments to be used
     * @param ?array $extract If defined, arguments are created based on this list
     * @return array|false
     */
    function create_payload(string $function_name, array $arguments, ?array $extract = null) :array|false{
        if(!function_exists($function_name)){
            return false;
        }

        $arguments = $extract ? create_arguments($arguments, $extract) : $arguments;

        return ["function" => $function_name, "arguments" => $arguments];
    }

    /**
     * This is used to fetch a single job to be processed
     * @param string $queue The name of the queue
     * @param int $max_attempts The maximum attempts to be done
     * @return array|false
     */
    function fetch_job(string $queue) :array|false{
        $current_time = time();
        return fetchData("*", "jobs", [
            "queue = '$queue'", 
            "(reserved_at IS NULL OR reserved_at < $current_time)",
            "available_at <= $current_time"],
            where_binds: "AND", order_by: "available_at");
    }

    /**
     * This is used to process the job
     * @param array $job The job array
     * @return bool
     */
    function process_job($job, $max_attempts) {
        global $last_exception;

        // get the payload details
        list("function" => $function, "arguments" => $arguments) = json_decode(unserialize_($job['payload']), true);
    
        // Simulate job execution (replace with actual logic)
        if(function_exists($function)){
            $response = $function(...$arguments);
    
            if($response === true) {
                delete("jobs", "id={$job['id']}");
            }else{
                if(($job["attempts"] + 1) < $max_attempts){
                    $data = [
                        "attempts" => $job["attempts"] + 1, 
                        "reserved_at" => time()
                    ];
                    update($job, $data, "jobs", ["id"]);
                }else{
                    // remove job and add to failed jobs
                    $data = [
                        "job_id" => $job["id"],
                        "queue" => $job["queue"],
                        "payload" => $job["payload"],
                        "exception" => $last_exception
                    ];
                    data_insert("failed_jobs", $data);
                    delete("jobs", "id={$job['id']}");
                }

                $response = false;
            }
        }

        $last_exception = null;
        return $response;
    }

    /**
     * This is used to run a worker
     * @param string $queue The name of the queue to run
     */
    function run_worker(string $queue, $max_attempts = 5) {
        $count = 0;
        while ($job = fetch_job($queue)) {
            if(process_job($job, $max_attempts)){
                ++$count;
            }
        }

        if($count){
            log_cron_message("$count '$queue' queues finished");
        }
    }

    /**
     * Delete a single tmp file (used as job target)
     * @param string $filepath absolute file path
     * @return bool true on success (or if file already missing), false on failure
     */
    function delete_tmp_file(string $filepath) : bool {
        // basic safety check: ensure file is inside your tmp dir
        $tmpDir = str_replace("\\", "/", relative_path("tmp"));
        $real = str_replace("\\","/", @realpath($filepath));

        if ($real === false) {
            // file doesn't exist
            return true;
        }

        // ensure file is under tmp dir to avoid accidental deletions
        if (strpos($real, $tmpDir) !== 0) {
            // attempted delete outside tmp -> fail and log
            // optionally set a global exception variable for failed job
            global $last_exception;
            $last_exception = "Unsafe delete attempt for file: $filepath";
            return false;
        }

        // Optionally, check file age so we don't delete prematurely if job was delayed incorrectly
        $maxAgeSeconds = 30; // if file is younger than 30s, skip (defensive)
        if (file_exists($real) && (time() - filemtime($real) < $maxAgeSeconds)) {
            // not old enough; requeue or return false to trigger attempt increment
            global $last_exception;
            $last_exception = "File too new to delete: $filepath";
            return false;
        }

        // attempt unlink
        if (unlink($real)) {
            return true;
        } else {
            global $last_exception;
            $last_exception = "Failed to unlink file: $filepath";
            return false;
        }
    }

    /**
     * Cleanup function: delete all files in dir older than $max_age seconds.
     * Useful for periodic cleanup jobs.
     *
     * @param string $dir absolute or relative to doc root (if relative, assumed under doc root)
     * @param int $max_age seconds
     * @return bool
     */
    function delete_tmp_cleanup(string $dir = 'tmp', int $max_age = 120) : bool {
        $base = $dir;
        if (!preg_match('#^/#', $dir)) {
            $base = $_SERVER['DOCUMENT_ROOT'] . "/$dir";
        }

        $base = realpath($base);
        if ($base === false || !is_dir($base)) {
            global $last_exception;
            $last_exception = "Cleanup dir not found: $dir";
            return false;
        }

        $files = glob($base . '/*'); // or '*' for any file type
        $ok = true;
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            $age = time() - filemtime($file);
            if ($age >= $max_age) {
                if (!@unlink($file)) {
                    $ok = false;
                    // record last exception for logging
                    global $last_exception;
                    $last_exception = "Failed to unlink $file";
                    // continue trying other files
                }
            }
        }

        return $ok;
    }

    require_once "auto-jobs.php";
