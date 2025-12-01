<?php 
    // put all helper functions in this file
    /**
     * This function determines what message should be displayed in a try/catch throwable block
     * @param Throwable $throwable This takes the throwable variable
     * @param ?string $additional_message An additional message to be logged into the error log file
     * @return string
     */
    function throwableMessage(Throwable $throwable, ?string $additional_message = null):string{
        global $developmentServer;
        
        $message = "";
        logThrowable($throwable, $additional_message);
        if($developmentServer){
            if(str_contains($_SERVER["SERVER_NAME"], ".local") === true)
                $message = $throwable->getMessage()." in ".$throwable->getFile()." on line ".$throwable->getLine();
            else
                $message = $throwable->getMessage();
        }else{
            $message = $throwable->getMessage();
        }

        return $message;
    }

    /**
     * Logs a throwable error
     * @param Throwable $throwable The throwable message
     * @param ?string $additional Additional message to be added
     */
    function logThrowable(Throwable $throwable, ?string $additional = null) {
        global $last_exception;
        $last_exception = $throwable;

        // Define the path to the logs directory
        $logDir = $_SERVER["DOCUMENT_ROOT"] . '/logs/'. date('F_Y');
        
        // Check if the logs directory exists, create it if it doesn't
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    
        // Define the log file name based on the current month and year
        $logFile = $logDir . '/log_' . date('d_m_Y') . '.log';
    
        // Gather error details
        $timestamp = date('Y-m-d H:i:s');
        $errorType = get_class($throwable);   // Get the exception/error class name
        $errorCode = $throwable->getCode();    // Get the error code (if any)
        $errorMessage = $throwable->getMessage();
        $errorFile = $throwable->getFile();
        $errorLine = $throwable->getLine();
        $errorTrace = $throwable->getTraceAsString() ?: "No stack trace available."; // Handle empty stack trace
    
        // Format the log entry
        $logEntry = "[$timestamp] Error Type: $errorType\n";
        $logEntry .= "Error Code: $errorCode\n";
        $logEntry .= "Message: $errorMessage\n";
        $logEntry .= "File: $errorFile (Line $errorLine)\n";
        $logEntry .= "Stack Trace:\n$errorTrace\n";

        if($additional){
            $logEntry .= "Additional Message: $additional\n";
        }

        $logEntry .= str_repeat("-", 80) . "\n"; // Separator for readability
    
        // Append the log entry to the log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Creates a url path
     * @param string $url The url
     * @return string
     */
    function url($path = '') {
        global $url; 
        return $url. '/' . ltrim($path, '/');
    }

    /**
     * Creates a path to an assets file
     * @param string $path The path to the file (from the assets directory)
     * @param bool $live set to true if it should have current changes per reload
     * @param bool $relative returns a relative path instead of a url
     * @return string 
     */
    function asset($path = '', $live = true, $relative = false) {
        $base = ltrim($path, '/') . ($live ? "?v=".time() : '');
        $path = "assets/$base";

        return $relative ? relative_path($path) : url($path);

    }

    /**
     * Creates a relative path using the rootpath
     * @param string $path The path to the file from the root
     * @return string
     */
    function relative_path($path = ''){
        global $rootPath;
        return $rootPath. '/' . ltrim($path, '/');
    }

    /**
     * Shortens a word/phrase into a code of at least 3 characters
     * @param string $text The text to be shortened
     * @return string
     */
    function shorten_to_code(string $text): string {
        // Remove any non-alphabetic characters
        $text = preg_replace('/[^a-zA-Z\s]/', '', $text);
        
        $words = explode(' ', strtoupper($text));
        $words = array_filter($words); // Remove empty elements
        $word_count = count($words);
        
        if ($word_count == 1) {
            // For single word, take first 3 characters minimum
            return substr($words[0], 0, max(3, min(4, strlen($words[0]))));
        } else if ($word_count == 2) {
            // For 2 words, take first char of first word and first 2 chars of second
            return substr($words[0], 0, 1) . substr($words[1], 0, 2);
        } else {
            // For 3+ words, take first letter of each word (up to 4 words)
            $code = '';
            $words = array_values($words); // Reindex array after filtering
            for ($i = 0; $i < min(4, $word_count); $i++) {
                $code .= substr($words[$i], 0, 1);
            }
            return $code;
        }
    }

    /**
     * This is used to lead a string with a zero
     * @param string $text The value to be transformed
     * @param int $length The desired length
     * @return string
     */
    function lead_by_zero(string $text, int $length = 2) :string{
        return str_pad($text, $length, "0", STR_PAD_LEFT);
    }

    /**
     * Get the current date and time
     * @param string $date Custom datetime or leave at now for current date
     * @param string $format The format to be used 
     * @param ?string $timezone The timezone to be used
     */
    function now(string $date = "now", string $format = "Y-m-d H:i:s", ?string $timezone = null){
        // set the timezone
        $timezone = $timezone ? new DateTimeZone($timezone) : null;
        $date = new DateTime($date, $timezone);
        return $date->format($format);
    }

    /**
     * This function is used to serialize a data
     * @param mixed $data The data to be serialized
     * @return string
     */
    function serialize_($data) :string{
        return base64_encode(serialize($data));
    }

    /**
     * This is used to unserialize a serialized datastring
     * @param string $datastring The serialized datastring
     * @param bool $json_to_array Converts a stored JSON string into an array
     * @return mixed The unserialized data
     */
    function unserialize_(string $datastring, bool $json_to_array = false): mixed {
        if(!function_exists("is_json")){
            /**
             * Check if a string is valid JSON
             * @param string $string The string data
             * @return bool
             */
            function is_json(string $string): bool {
                if (!is_string($string)) {
                    return false;
                }
                json_decode($string);
                return json_last_error() === JSON_ERROR_NONE;
            }
        }

        // Decode base64
        $decoded = base64_decode($datastring, true);
        if ($decoded === false) {
            return false;
        }

        // Attempt to unserialize
        $unserialized_data = @unserialize($decoded);
        if ($unserialized_data === false && $decoded !== 'b:0;') { // 'b:0;' is serialized false
            return false; // Unserialization failed
        }

        // Convert JSON string to an array if requested
        if ($json_to_array && is_string($unserialized_data) && is_json($unserialized_data)) {
            return json_decode($unserialized_data, true);
        }

        return $unserialized_data;
    }

    /**
     * Used to pluck an array to the form [key => value, key => value]...
     * If $value is "array", it will store the remainder of the keys as an array.
     * All internal keys are uppercase by default
     * @param $array The array. Rejects non-arrays
     * @param string $key The key values
     * @param string $value The value key or use "array" to store the remainder
     * @param bool $reserve_keys Set to true if it should reserve the internal keys in the default format
     * @param array $rename Use this to rename columns to different names. Used especially for value = 'array'
     * @return array
     */
    function pluck(mixed $array, string $key, string $value, bool $reserve_keys = false, array $rename = []) :array{
        $response = [];

        if(empty($array) || !is_array($array)){
            return $response;
        }
        
        array_map(function($object) use (&$response, $key, $value, $reserve_keys, $rename){
            $keyValue = strtoupper($object[$key]);

            if ($value === 'array') {
                unset($object[$key]);
                $response[$keyValue] = $reserve_keys ? $object : array_change_key_case($object, CASE_UPPER);

                if($rename){
                    foreach($response as $n_key => $n_response){
                        if(is_array($n_response)){
                            foreach($rename as $existing_key => $new_key){
                                if(isset($n_response[$existing_key])){
                                    $n_response[$new_key] = $n_response[$existing_key];
                                    unset($n_response[$existing_key]);
                                }
                            }
                        }
                        $response[$n_key] = $n_response;
                    }
                }
            } else {
                $response[$keyValue] = strtoupper($object[$value]);
            }
        }, $array);

        return $response;
    }

    /**
     * This retrieves or set a session variable
     * @param string $name The name in the session variable
     * @param $value When empty, it returns the variable name
     * @return mixed
     */
    function session(string $name, $value = null){
        // detect dot notation
        $keys = explode('.', $name);

        // ---------------------------------
        // 1. Setting session value
        // ---------------------------------
        if ($value !== null) {

            $ref = &$_SESSION;

            foreach ($keys as $key) {
                if (!isset($ref[$key]) || !is_array($ref[$key])) {
                    $ref[$key] = [];
                }
                $ref = &$ref[$key];
            }

            $ref = $value;
            return $value;
        }

        // ---------------------------------
        // 2. Getting session value
        // ---------------------------------
        $ref = $_SESSION;

        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                return null; // missing key
            }
            $ref = $ref[$key];
        }

        return $ref;
    }

    /**
     * This function is used to help build a where array for queries
     * @param array $filters The filters to be applied
     * @param ?array $mapping The mapping of filter keys to database columns
     *              If mapping is left as null, it means there is a global variable called mapping which will be used
     * @return array
     */
    function buildWhereClause(array $filters, ?array $mapping = null): array {
        $where = [];

        if ($mapping === null && isset($GLOBALS['mapping'])) {
            $mapping = $GLOBALS['mapping'];
        }

        if(!empty($mapping)){
            foreach ($mapping as $key => $column) {
                if (!empty($filters[$key])) {
                    $where[] = "$column = '{$filters[$key]}'";
                }
            }
        }
        

        return $where;
    }

    /**
     * This returns a list of teacher ranks
     * @return array
     */
    function teacher_ranks(): array {
        return [
            "Principal Chief Instructor" => "Principal Chief Instructor",
            "Chief Instructor" => "Chief Instructor",
            "Assistant Lecturer" => "Assistant Lecturer",
            "Lecturer" => "Lecturer",
            "Senior Lecturer" => "Senior Lecturer",
            "Associate Professor" => "Associate Professor",
            "Professor" => "Professor"
        ];
    }

    /**
     * Returns a list of all the teacher roles we have
     * @param bool $as_list Set to 'list' to return a simple list of roles (by their actual names)
     * @param bool $key_only Used with as_list, if set to true, returns only the keys, else the display names of the keys
     * @return array
     */
    function get_system_user_roles(bool $as_list = false, bool $key_only = false): array {
        $roles = [
            'admin' => 'Administrator',
            'teacher' => 'Teacher',
            'student' => 'Student',
        ];

        if($as_list){
            return !$key_only ? array_values($roles) : array_keys($roles);
        }

        return $roles;
    }

    /**
     * This gets the current academic year
     * @return string The current academic year in the format YYYY/YYYY
     */
    function getCurrentAcademicYear() {
        $current_month = (int)date('m');
        $current_year = (int)date('Y');
        
        if ($current_month >= 9) {
            $start_year = $current_year;
            $end_year = $current_year + 1;
        } else {
            $start_year = $current_year - 1;
            $end_year = $current_year;
        }
        return "{$start_year}/{$end_year}";
    }

    /**
     * This provides a route
     */
    function route($name, $params = []){
        global $namedRoutes;

        if (!isset($namedRoutes[$name])) {
            $matched = false;

            // if name contains dot, convert to slash and check again
            if(str_contains($name, '.')){
                $converted_name = str_replace('.', '/', $name);
                if(isset($namedRoutes[$converted_name])){
                    $name = $converted_name;
                    $matched = true;
                }
            }

            if(!$matched){
                throw new Exception("Route '{$name}' not found.");
            }
        }

        $path = $namedRoutes[$name];

        // replace dynamic parameters
        foreach ($params as $key => $value) {
            $path = str_replace("{" . $key . "}", $value, $path);
        }

        // Use your existing url() helper to fully qualify it
        return url($path);
    }
    
    /**
     * This is used to add routes to the end of script files. Use in templates for better performance
     */
    function add_named_routes(){
        global $namedRoutes;
        $encoded_routes = json_encode($namedRoutes);

        $script =<<<HTML
        <script>
            window.namedRoutes = $encoded_routes;
        </script>
        HTML;

        return $script;
    }

