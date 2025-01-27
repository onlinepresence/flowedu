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
     * This flushes session variables expected to last a time
     */
    function flush_session(){
        unset($_SESSION["errors"], $_SESSION["old_input"]);
    }
