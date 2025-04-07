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
    function login(){
        $_SESSION["old_input"] = $_POST;
        $errors = [];
        $data = form_data();
        $response = false;

        if(empty($data["email"])){
            $errors["email"] = "Please provide an email";
        }elseif(!filter_var($data["email"], FILTER_VALIDATE_EMAIL)){
            $errors["email"] = "Please provide a valid email";
        }

        if(empty($data["password"])){
            $errors["password"] = "Please provide a password";
        }

        if(!$errors){
            // check if user can be found
            $user = fetchData("id, password, type", "users", "email='{$data['email']}'");
            if($user){
                if(password_verify($data["password"], $user["password"])){
                    create_user_session($user["type"], $user["id"]);
                    user(true);
                    return url($user["type"]."/dashboard");
                }else{
                    $errors["password"] = "Password provided is incorrect";
                }
            }else{
                $errors["email"] = "User with the specified email was not found";
            }
        }

        if($errors){
            $_SESSION["errors"] = $errors;
        }

        send_to_next_request();

        return $response;
    }

    /**
     * This creates a session for a logged in user
     * @param string $type The user type
     * @param int $user_id The user id
     */
    function create_user_session($type, $user_id){
        $_SESSION["user_id"] = $user_id;

        if($type == "admin" && (!isset($_SESSION["admin_register"]) || $_SESSION["admin_register"] == false)){
            $type = fetchData("name", ["join" => "admins admin_types", "on" => "type id", "alias" => "a t"], "user_id=$user_id")["name"] ?? "unknown";
        }
        
        $_SESSION["user_type"] = $type;
    }

    /**
     * This flushes session variables expected to last a request
     */
    function flush_session(){
        global $last_exception;

        if(!isset($_SESSION["message_to_next_request"])){
            unset(
                $_SESSION["errors"], $_SESSION["old_input"], $_SESSION["system_message"], $_SESSION["system_warning"],
                $_SESSION["toast_messages"]
            );
        }

        unset($_SESSION["message_to_next_request"]);

        $last_exception = null;
    }

    /**
     * creates the message to last two requests
     */
    function send_to_next_request(){
        $_SESSION["message_to_next_request"] = true;
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
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
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
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This gets all or specified programs in the system
     * @param int $id The id of the program
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function programs($id = null, $complete = false, $columns = []){
        $where = $id ? "id = $id" : [];
        $tables = $complete ? ["join" => "programs departments", "on" => "department_id id", "alias" => "p d"] : "programs";
        
        if(!$complete && !$columns){
            $columns = ["id", "name", "department_id", "certificate", "cost"];
        }elseif($complete){
            $columns = ["p.id", "p.name", "department_id", "certificate", "cost", "d.name as department_name"];
        }
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This gets all or specified halls in the system
     * @param int $id The id of the hall
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function halls($id = null, $complete = false, $columns = []){
        $where = $id ? "id = $id" : [];
        $tables = "halls";
        
        if(!$columns){
            $columns = ["id", "name", "master", "cost", "period"];
        }
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * Used to format the hall period
     * @param ?string $period The period from the db
     * @return string
     */
    function format_hall_period(?string $period){
        if(!$period){
            return "Per Year";
        }

        $period = str_replace("_", " ", $period);
        $period = ucwords(strtolower($period));

        return $period;
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
            $user = fetchData($columns, $table, "u.id = $userId", join_type: "left");
            
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
     * This is used to get a complete information on a user
     * @param int $id The user id
     * @param string|array $columns Specific columns to return
     * @return array|false 
     */
    function get_user(int $id, $columns = null) :array|false{
        return fetchData($columns ?? "id, username, email, type, active", "users", "id = $id");
    }

    /**
     * gets a complete information of a user
     * @param int $id The user id
     * @param ?string $type The user type if specified
     * @return array|false
     */
    function get_user_details(int $id, ?string $type = null) :array|false{
        $user = false;

        if(!$type && ($type = fetchData("type", "users", "id=$id"))){
            $type = $type["type"];
        }

        // get the user type
        if($type){
            $columns = get_user_columns($type);
            $table = get_user_table($type);
            $user = fetchData($columns, $table, "u.id = $id", join_type: "left");
        }

        return $user;
    }

    /**
     * This retrieves the columns for the currently logged in user
     * @param ?string $type The specified user type.
     * @return array
     */
    function get_user_columns(?string $type = null) :array{
        $default = ["u.id", "user_id", "username", "email", "lastname", "othernames", "email_verified_at", "u.active"];
        $type = $type ?? $_SESSION["user_type"];

        switch($type){
            case "admin":
            case "hod":
            case "owner":
            case "dean":
                $cols = ["a.id as admin_id", "a.type", "name AS admin_type", "display_name"];
                break;
            case "student":
                $cols = [
                    "s.id AS student_id", "index_number", "department_id", "program_id", "profile_pic",
                    "date_of_birth", "gender", "nationality", "religion", "current_year",
                    "contact_address", "phone_number", "admission_date", "graduated",
                    "allergy", "insurance_number", "hall_id", "is_new", "approved"
                ];
                break;
            case "teacher":
                $cols = ["t.id AS teacher_id"];
                break;
            default:
                $cols = [];
        }

        return array_merge($default, $cols);
    }

    /**
     * This gets the user tables
     * @param ?string $type The specified user type.
     * @return array
     */
    function get_user_table(?string $type = null) :array{
        $type = $type ?? $_SESSION["user_type"];

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
                    "join" => "users students", "on" => "id user_id", "alias" => "u s"
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
     * This generates an index number for a student during admission
     * @return string
     */
    function generate_admission_index(){
        $year = date("y");
        do {
            $index_number = str_shuffle(substr(uniqid(), 0, 8));
            $index_number .= $year;
        } while (fetchData("index_number", "students", "index_number = '$index_number'"));

        return $index_number;
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
     * This generates an index number for a specified student
     * @return string|false
     */
    function create_index_number() :string|false{
        // user needs to be logged in for this to happen
        if(!isset($_SESSION["user_id"]) || $_SESSION["user_type"] != "student" || !user()['approved']){
            return false;
        }

        $user = user(); $school = school();

        if(intval($student_id_ = $user['student_id'])){
            do {
                $school_id = lead_by_zero($school["id"]);
                $student_id = lead_by_zero($student_id_++, 4);
                $department_id = lead_by_zero($user["department_id"]);
                $year = date("y");

                $index_number = $school_id.$year.$department_id.$student_id;
            } while (fetchData("id", "students", "index_number = '$index_number' AND id != ".user()["student_id"]));
        }

        return $index_number ?? false;
    }

    /**
     * Returns the details of the school
     * @return array|false
     */
    function school(){
        return fetchData("*", "schools", "id = 1");
    }

    /**
     * This gets all the nationalities
     */
    function nationalities() {
        $file = 'nationalities.json';
        $oneMonth = 30 * 24 * 60 * 60; // 30 days in seconds
    
        // Check if file exists and its last update time
        if (file_exists($file) && (time() - filemtime($file) < $oneMonth)) {
            $nationalities = json_decode(file_get_contents($file), true); // Return cached data
        }else{
            $apiUrl = "https://restcountries.com/v3.1/all?fields=demonyms";
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if (!$response) {
                $nationalities = file_exists($file) ? json_decode(file_get_contents($file), true) : []; // Fallback to old data if API fails
            }else{
                $countries = json_decode($response, true);
                $nationalities = [];
            
                foreach ($countries as $country) {
                    if (isset($country['demonyms']['eng']['m'])) {
                        $nationalities[] = $country['demonyms']['eng']['m'];
                    }
                }
            
                // Save to JSON file
                file_put_contents($file, json_encode($nationalities, JSON_PRETTY_PRINT));
            }
        }
        
        sort($nationalities, SORT_STRING);
        return $nationalities;
    }

    /**
     * This creates a user account in the users table
     */
    function create_new_user(){
        global $connect;
        $errors = [];

        $email = $_POST["email"] ?? null;
        $password = $_POST["password"] ?? null;
        $password_confirm = $_POST["password_confirm"] ?? null;
        $type = $_POST["type"] ?? null;
        $admin_register = $_POST["admin_register"] ?? null;
        $system_secret = $_POST["system_secret"] ?? null;

        if(empty($email)){
            $errors["email"] = "No email provided";
        }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors["email"] = "Invalid email provided";
        }if(empty($password)){
            $errors["password"] = "No password provided";
        }if(empty($password_confirm)){
            $errors["password_confirm"] = "No confirmation password provided";
        }if(strlen($password) < 8){
            $errors["password"] = "Password provided should be at least 8 characters";
        }if(strcmp($password, $password_confirm) != 0){
            $errors["password"] = "Passwords do not match";
        }if($admin_register == 1 && empty($system_secret)){
            $errors["system_secret"] = "System secret is needed to activate it";
        }if($admin_register == 1 && !check_secret($system_secret)){
            $errors["system_secret"] = "System secret provided is not valid";
        }

        if(!$errors){
            $data = form_data(exclude: ["system_secret", "admin_register", "password_confirm"]);
            $data["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
            $data["user_secret"] = generate_user_secret();
            $response = data_insert("users", $data);
            if($response){
                create_user_session($type, $connect->insert_id);

                // send verification email
                if(send_verification_email() !== false){
                    $_SESSION["system_message"] = "An email verification message has been sent to your email";
                    send_to_next_request();
                }
                
                if($admin_register == 1){
                    $next_request = "admin-setup/personal";
                }else{
                    $next_request = "student-setup/personal";
                }
            }
        }else{
            $_SESSION["errors"] = $errors;
        }

        return $next_request ?? null;
    }

    /**
     * This creates a secret key for the user
     */
    function generate_user_secret(){
        return bin2hex(random_bytes(32));
    }

    /**
     * gets the user secret key
     * @param int $user_id
     * @return string|false
     */
    function get_user_secret(int $user_id) :string|false{
        if($secret = fetchData("user_secret", "users", "id=$user_id")){
            $secret = $secret["user_secret"];
        };

        return $secret;
    }

    /**
     * This gets the guardian information for a specified student
     * @return array|false;
     */
    function guardian(){
        return fetchData("id,name,relationship,address, phone_number,email", "parent_guardians", "student_id = ".user()['student_id']);
    }

    /**
     * This is usually called when a user with a profile pic makes an update. It removes an old profile pic and make the replacement where the need be
     */
    function reset_profile_pic(){
        if(!empty($profile_pic = user()["profile_pic"])){
            unlink(asset($profile_pic, false, true));
        }
    }

    /**
     * Used to validate if a phone number is valid
     * @param string $phone The phone number to be checked
     * @param ?string $provider The service provider
     * @param int
     */
    function is_valid_phone_number(string $phone, ?string $provider = null) :int {
        // Extract the first 3 digits of the phone number
        $prefix = substr($phone, 0, 3);
        
        // If a provider is specified, validate it
        if ($provider) {
            global $provider_prefixes;

            if (!isset($provider[$provider])) {
                return -1;
            }
            return in_array($prefix, $provider_prefixes[$provider]);
        }

        global $phone_prefixes;

        // Check if the prefix exists in the array
        return in_array($prefix, $phone_prefixes);
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
     * This is used to generally delete an item from the database. It is basically used together with the delete item component
     */
    function delete_item($table, $id, $column = "id"){
        return delete($table, "$column = $id");
    }

    require_once "mailer_functions.php";
    require_once "jobs.php";
    require_once "student_function.php";