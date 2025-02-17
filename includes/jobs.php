<?php
    /**
     * This is used to add a new job to the jobs table
     * @param string $queue The type of queue this job belongs to
     * @param array $payload The payload
     * @param int $max_attempts The maximumn number attempts till it is determined a failed job
     * @return bool
     */
    function add_job(string $queue, array $payload) :bool {
        $time = time();
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
            }
        }

        $last_exception = null;
    }

    /**
     * This is used to run a worker
     * @param string $queue The name of the queue to run
     */
    function run_worker(string $queue, $max_attempts = 5) {
        while ($job = fetch_job($queue)) {
            process_job($job, $max_attempts);
        }
    }