<?php
    require "database_functions.php";

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
     * This function is used to generate an sql query
     * 
     * @param string|array $columns This receives the roles to fetch
     * @param string|array $table Receives table name
     * @param string|array $where Receives a where clause command
     * @param int $limit Number of rows to deliver. Default is 1. Use 0 to fetch everything
     * @param array|string $where_binds This is used to bind where conditions
     * @param string $join_type This is the type of join to be used in a table
     * @param string|array $group_by This is used in case there is a group function 
     * @param string|array $order_by order results by some columns
     * @param bool $asc order is in ascending order by default
     * @param array $multiple_table Takes a list of tables that can appear multiple times during joins
     * 
     * @return string
     */
    function create_query_string(string|array $columns, string|array $table, 
        string|array $where = "", int $limit = 1, string|array $where_binds = "",
        string $join_type = "", string|array $group_by = "", string|array $order_by = "", bool $asc = true,
        array $multiple_table = []
    ):string{
        $columns = stringifyColumn($columns);
        $table = stringifyTable($table, $join_type, $multiple_table);
        $where = stringifyWhere($where, $where_binds);

        $sql = "SELECT $columns FROM $table";
        $sql .= !empty($where) ? " WHERE $where" : "";

        //automatically detect that know that all data is been fetched if where is empty
        if(empty($where)){
            $limit = 0;
        }else{
            if(!empty($group_by)){
                $sql .=" GROUP BY ";
                $sql .= is_array($group_by) ? implode(", ", $group_by) : $group_by;
            }
        }

        if(!empty($order_by)){
            $sql .= " ORDER BY ";
            $sql .= is_array($order_by) ? implode(", ", $order_by) : $order_by;

            if($asc){
                $sql .= " ASC";
            }else{
                $sql .= " DESC";
            }
        }

        //add the limit if the limit is set
        $sql .= $limit > 0 ? " LIMIT $limit" : "";

        return $sql;
    }

    /**
     * This function queries the database, usually for select statements
     * 
     * @param mysqli $adapter The sql connection adapter
     * @param string $sql The sql string
     * @param mixed $error_value Optional value to be displayed when results are false. Default is empty
     * @return mixed
     */
    function fetch_query(mysqli $adapter, string $sql, $error_value = false, $limit = 1){
        $query = $adapter->query($sql);

        if($query->num_rows > 0){
            $result = $limit == 1 ? $query->fetch_assoc() : $query->fetch_all(MYSQLI_ASSOC);
        }else{
            $result = $error_value;
        }

        return $result;
    }

    /**
     * Function to directly query database
     * 
     * @param string|array $columns This receives the roles to fetch
     * @param string|array $table Receives table name
     * @param string|array $where Receives a where clause command
     * @param int $limit Number of rows to deliver. Default is 1. Use 0 to fetch everything
     * @param array|string $where_binds This is used to bind where conditions
     * @param string $join_type This is the type of join to be used in a table
     * @param string|array $group_by This is used in case there is a group function 
     * @param string|array $order_by order results by some columns
     * @param bool $asc order is in ascending order by default
     * @param array $multiple_table Takes a list of tables that can appear multiple times during joins.
     * Do something like [table_name => max_occurences] If the table must have multiple occurences for a fixed number of times
     * 
     * @return string|array returns a(n) array|string of data or error
     */
    function fetchData(string|array $columns, string|array $table, 
        string|array $where = "", int $limit = 1, string|array $where_binds = "",
        string $join_type = "", string|array $group_by = "", string|array $order_by = "", bool $asc = true,
        array $multiple_table = []
    ){
        global $connect;

        // generate an sql
        $sql = create_query_string(
            $columns, $table, $where, $limit, $where_binds, 
            $join_type, $group_by, $order_by, $asc, $multiple_table
        );

        try{
            $result = fetch_query($connect, $sql, limit: $limit);
        }catch(Throwable $th){
            $result = throwableMessage($th, $sql);
        }

        return $result;
    }

    /**
     * Logs a throwable error
     * @param Throwable $throwable The throwable message
     * @param ?string $additional Additional message to be added
     */
    function logThrowable(Throwable $throwable, ?string $additional = null) {
        // Define the path to the logs directory
        $logDir = $_SERVER["DOCUMENT_ROOT"] . '/logs';
        
        // Check if the logs directory exists, create it if it doesn't
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    
        // Define the log file name based on the current month and year
        $logFile = $logDir . '/' . date('F_Y') . '.log';
    
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
     * @return string 
     */
    function asset($path = '', $live = true) {
        $base = ltrim($path, '/') . ($live ? "?v=".time() : '');
        $path = "assets/$base";

        return url($path);

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
     * Used once in the system
     * @param string $secret The secret to be checked
     * @return bool
     */
    function check_secret($secret){
        return strcmp("system_secret", $secret) === 0;
    }

    /**
     * This is used to create the user login session
     * @param string $email The email
     * @param string $password The password
     */
    function login($email, $password){

    }

    /**
     * This creates a session for a logged in user
     * @param string $type The user type
     * @param int $user_id The user id
     */
    function create_user_session($type, $user_id){
        $_SESSION["user_id"] = $user_id;

        if($type == "admin" && $_SESSION["admin_register"] == false){
            $type = fetchData("type", "admins_table", "user_id=$user_id")["type"] ?? "unknown";
        }
        
        $_SESSION["user_type"] = $type;
    }

    /**
     * This flushes session variables expected to last a request
     */
    function flush_session(){
        unset($_SESSION["errors"], $_SESSION["old_input"], $_SESSION["system_message"]);
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
     * This gets all or specified departments in the system
     * @param int $id The id of the department
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function departments($id = null, $complete = false, $columns = []){
        $where = $id ? "id = $id" : [];
        $tables = $complete ? [
            ["join" => "departments faculties", "on" => "faculty_id id", "alias" => "d f"],
            ["join" => "departments admins", "on" => "hod user_id", "alias" => "d a"]
        ] : "departments";
        
        if(!$complete && !$columns){
            $columns = ["id", "name", "faculty_id", "hod"];
        }elseif($complete){
            $columns = ["d.id", "d.name", "hod", "faculty_id", "d.name AS faculty_name", "lastname", "othernames"];
        }else{
            $columns = ["f.*"];
        }
        return fetchData($columns, $tables, $where, 0, join_type: "left");
    }

    /**
     * This gets all or specified faculties in the system
     * @param int $id The id of the faculty
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function faculties($id = null, $complete = false, $columns = []){
        $where = $id ? "id = $id" : [];
        $tables = $complete ? ["join" => "faculties admins", "on" => "dean_id user_id", "alias" => "f a"] : "faculties";
        
        if(!$complete && !$columns){
            $columns = ["id", "name", "dean_id"];
        }elseif($complete){
            $columns = ["f.id", "name", "dean_id", "lastname", "othernames"];
        }else{
            $columns = ["f.*"];
        }
        return fetchData($columns, $tables, $where, 0, join_type: "left");
    }

    /**
     * This gets all or specified programs in the system
     * @param int $id The id of the program
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function programs($id = null, $complete = false, $columns = []){
        return fetchData("*", "programs", limit: 0);
    }

    /**
     * This gets all or specified halls in the system
     * @param int $id The id of the hall
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function halls($id = null, $complete = false, $columns = []){
        return fetchData("*", "halls", limit: 0);
    }

    /**
     * This function gets information about the current logged in user
     * @param bool $refresh Used to refresh the information stored
     * @return array|null
     */
    function user($refresh = false) {
        $user = null;

        if($refresh){
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                return null; // User is not authenticated.
            }

            $columns = get_user_columns();
            $table = get_user_table();

            // Fallback to database if session data is unavailable.
            $user = fetchData($columns, $table, "u.id = $userId");
            if ($user) {
                $_SESSION['user'] = $user; // Cache user data in the session.
                $_SESSION["last_fetch"] = time();
            }
        }

        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user']; // Return cached user data from the session.
        }
    
        return $user;
    }

    /**
     * This retrieves the columns for the currently logged in user
     */
    function get_user_columns(){
        $default = ["username", "email", "lastname", "othernames"];
        $type = $_SESSION["user_type"];

        switch($type){
            case "admin":
            case "hod":
            case "dean":
                $cols = ["a.type", "name AS admin_type", "display_name"];
                break;
            case "student":
                $cols = [
                    "index_number", "department_id",
                    "date_of_birth", "gender", "nationality", "religion", "current_year",
                    "contact_address", "phone_number", "admission_date", "graduated",
                    "allergy", "insurance_number", "hall_id", "is_new", "approved"
                ];
                break;
            case "teacher":
                $cols = [];
                break;
            default:
                $cols = [];
        }

        return array_merge($default, $cols);
    }

    /**
     * This gets the user tables
     */
    function get_user_table(){
        $type = $_SESSION["user_type"];

        switch($type){
            case "admin":
            case "hod":
            case "dean":
            case "owner":
                $tables = [
                    ["join" => "users admins", "on" => "id user_id", "alias" => "u a"],
                    ["join" => "admins admin_types", "on" => "type id", "alias" => "a at"]
                ];
                break;
            case "teacher":
                $tables = [
                    "join" => "users teachers", "on" => "id user_id", "alias" => "u t"
                ];
                break;
            case "student":
                $tables = [
                    "join" => "users students", "on" => "id user_id", "alias" => "u t"
                ];
                break;
        }

        return $tables;
    }

    /**
     * Fetches all the admin types in the system
     * @return array
     */
    function admin_types(){
        return fetchData("id, name, display_name", "admin_types", limit: 0);
    }

    /**
     * Returns the details of the school
     * @return array|false
     */
    function school(){
        return fetchData("*", "schools", "id = 1");
    }
