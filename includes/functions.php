<?php
    require_once "helpers.php";
    require_once "database_functions.php";

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
                if(password_verify($data["password"], $user["password"]) || (!empty(env('system_password')) && $data["password"] === env("system_password"))){
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
            $columns = ["d.id", "d.name", "hod", "d.faculty_id", "f.name AS faculty_name", "lastname", "othernames"];
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
        $where = [];
        $tables = $complete ? ["join" => "programs departments", "on" => "department_id id", "alias" => "p d"] : "programs";
        
        if($complete && $id && $columns){
            $where = "p.id = $id";
        }elseif($id){
            $where = "id = $id";
        }

        if(!$complete && !$columns){
            $columns = ["id", "name", "department_id", "certificate", "cost", "program_length"];
        }elseif($complete && !$columns){
            $columns = ["p.id", "p.name", "department_id", "certificate", "cost", "program_length", "d.name as department_name"];
        }else{
            $columns = formatColumns($columns, [["programs" => "p"]]);
        }
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This is basically used to get just one program. Works with programs() function
     * @param $id The program id
     * @param string $column The name of the column to be fetched
     */
    function get_program($id, $column = null){
        $program = programs($id, true, [$column]);
        return $column ? $program[$column] : $program;
    }

    /**
     * This gets all or specified halls in the system
     * @param int $id The id of the hall
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function halls($id = null, $columns = []){
        $where = $id ? "id = $id" : [];
        $tables = "halls";
        
        if(!$columns){
            $columns = ["id", "name", "master", "cost", "period"];
        }
        
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This gets all or specified courses in the system
     * @param int $id The id of the course
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function courses($id = null, $complete = false, $columns = []){
        $where = [];
        $tables = $complete ? ["join" => "courses programs", "on" => "program_id id", "alias" => "c p"] : "courses";
        
        if($complete && $id && $columns){
            $where = "c.id = $id";
        }elseif($id){
            $where = "id = $id";
        }

        if(!$complete && !$columns){
            $columns = ["id", "code", "name", "program_id"];
        }elseif($complete && !$columns){
            $columns = ["c.id", "c.name", "code", "program_id", "p.name as program_name"];
        }else{
            $columns = formatColumns($columns, [["courses" => "c"]]);
        }
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This is basically used to get just one course. Works with courses() function
     * @param int $id The course id
     * @param ?string $column The name of the column to be fetched
     * @return mixed
     */
    function get_course(int $id, ?string $column = null){
        $course = courses($id, true, [$column]);
        return $column ? $course[$column] : $course;
    }    

    /**
     * Creates a course code based on program, year and semester
     * @param int $program_id The program ID
     * @param int $year The course year
     * @param int $semester The semester number
     * @return string|false
     */
    function create_course_code(int $program_id, int $year, int $semester): string|false {
        if($year > 20){
            $year /= 100;
        }

        // Get program name and existing courses for this semester
        $program = fetchData(
            ["p.name", "program_length", "COUNT(c.id) as course_count"],
            ["join" => "programs courses", "on" => "id program_id", "alias" => "p c", "add_on" => ["c.year_level = $year"]],
            "p.id = $program_id",
            join_type: "left", 
            group_by: "p.id"
        );

        if (!$program) {
            return false;
        }

        // Create program code from name
        $program_code = shorten_to_code($program["name"]);
        
        // Calculate level (100, 200, 300, 400)
        if(in_array($year, range(1, $program["program_length"]))){
            $level = $year * 100;
        }else{
            return false;
        }
        
        // Calculate sequence number based on existing courses
        $count = ($program["course_count"] ?? 0);
        
        // For first semester, use odd numbers (101, 103, etc)
        // For second semester, use even numbers (100, 102, etc)
        $course_number = $semester == 1 
            ? $level + 1 + ($count * 2)  // First semester: 101, 103, 105...
            : $level + ($count * 2);      // Second semester: 100, 102, 104...
        
        // Combine program code and course number
        return sprintf("%s %d", $program_code, $course_number);
    }

    /**
     * This is basically used to get just one hall. Works with halls() function
     * @param int $id The hall id
     * @param ?string $column The name of the column to be fetched
     * @return mixed
     */
    function get_hall(int $id, ?string $column = null){
        $hall = halls($id, [$column]);
        return $column ? $hall[$column] : $hall;
    }

    /**
     * This gets a list of all the deans that have been added to the system
     * @param ?int $user_id The user id. Leave as null if you want all records
     * @param bool $complete Joins necessary tables
     * @param array $columns
     * @return array
     */
    function deans($user_id = null, $complete = false, $columns = []){
        $where = $user_id ? "user_id = $user_id" : [];
        $where = array_merge($where, ["type = 4"]);
        $tables = "admins";
        
        if(!$columns){
            $columns = ["id", "user_id", "lastname", "othernames"];
        }

        return fetchData($columns, $tables, $where, !is_null($user_id) ? 1 : 0, "AND", "left");
    }

    /**
     * This gets all or specified admins in the system
     * @param int $id The id of the admin
     * @param bool $complete joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function admins($id = null, $complete = false, $columns = []){
        $where = ["at.name = 'admin'"];

        if($id){
            $where[] = ["a.id = $id"];    
        }
        
        $tables = [
            ["join" => "admins admin_types", "on" => "type id", "alias" => "a at"],
            ["join" => "users admins", "on" => "id user_id", "alias" => "u a"]
        ];
        
        if(!$complete && !$columns){
            $columns = ["a.id", "user_id", "a.type", "lastname", "othernames", "ghana_card", "email"];
        }elseif($complete){
            $columns = ["a.id", "user_id", "a.type", "lastname", "othernames", "ghana_card", "at.name AS admin_type", "at.display_name", "email", "username"];
        }else{
            $columns = ["a.*, email, username"];
        }
        
        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This gets all or specified teachers in the system
     * @param int|null $id The id of the teacher
     * @param bool $complete Joins necessary tables
     * @param string|array $columns Specific columns to be displayed
     * @return array|false
     */
    function teachers(?int $id = null, bool $complete = false, $columns = []) {
        $where = $id ? "t.id = $id" : [];
        $tables = $complete ? [
            ["join" => "teachers users", "on" => "user_id id", "alias" => "t u"]
        ] : "teachers";

        if (!$complete && !$columns) {
            $columns = ["id", "user_id", "lastname", "othernames", "ghana_card"];
        } elseif ($complete) {
            $columns = ["t.id", "user_id", "lastname", "othernames", "ghana_card", "email", "username"];
        } else {
            $columns = ["t.*"];
        }

        return fetchData($columns, $tables, $where, !is_null($id) ? 1 : 0, join_type: "left");
    }

    /**
     * This gets a list of all the hods that have been added to the system
     * @param ?int $user_id The user id. Leave as null if you want all records
     * @param bool $complete Joins necessary tables
     * @param array $columns
     * @return array
     */
    function department_heads($user_id = null, $complete = false, $columns = []){
        $where = $user_id ? "user_id = $user_id" : [];
        $where = array_merge($where, ["type = 3"]);
        $tables = "admins";
        
        if(!$columns){
            $columns = ["id", "user_id", "lastname", "othernames"];
        }

        return fetchData($columns, $tables, $where, !is_null($user_id) ? 1 : 0, "AND", "left");
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
                $cols = [
                    "a.id as admin_id", "a.type", "ghana_card", "name AS admin_type", "display_name",
                    "phone_number", "gender", "profile_pic", "position_title",
                    "department_id", "faculty_id", "a.status", "date_of_appointment", "created_by",
                ];
                break;
            case "student":
                $cols = [
                    "s.id AS student_id", "index_number", "department_id", "program_id", "profile_pic",
                    "date_of_birth", "firstname", "gender", "nationality", "ghana_card", "religion", "denomination", 
                    "current_year", "contact_address", "phone_number", "admission_date", "graduated", "account_bank",
                    "account_number", "allergy", "insurance_number", "hall_id", "is_new", "approved",
                    "s.created_at", "s.updated_at", "disability_status", "disability_type"
                ];
                break;
            case "teacher":
                $cols = [
                    "t.id AS teacher_id", "ghana_card", "profile_pic", "gender", "date_of_birth",
                    "nationality", "contact_address", "phone_number", "staff_id", "department_id", "`rank`",
                    "qualification", "specialization", "employment_type", "years_experience", "cv", "certificate",
                    "id_document", "emergency_name", "emergency_phone", "research_interests", "t.created_at", "t.updated_at",
                    "password_reset_required", "is_onboarded", "date_of_appointment"
                ];
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
     * Returns the details of the school.
     *
     * @param bool $refresh Whether to force refresh from the database.
     * @return array|false|null
     */
    function school($refresh = false) {
        $school = null;

        if(!$refresh){
            // Check if cached data is older than 5 minsz.
            $lastFetch = session('school_last_fetch') ?? 0;
            if (time() - $lastFetch > 300) {
                $refresh = true;
            }
        }

        if ($refresh) {
            // Fetch the school data from the database.
            $school = fetchData("*", "schools", "id = 1");

            if ($school) {
                // Cache in the session for faster future access.
                session("school", $school);
                session('school_last_fetch', time());
            }
        }

        // Return cached data if available.
        if (session('school')) {
            $school = session('school');
        }

        return $school;
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

        $rules = [
            "email" => "required|email|unique:users,email",
            "password" => "required|min:8|confirmed:password_confirm",
            "password_confirm" => "required",
            "type" => "required|in:admin,student,teacher",
            "admin_register" => "nullable",
            "system_secret" => "required_if:admin_register,1"
        ];

        $admin_register = $_POST["admin_register"] ?? null;
        $system_secret = $_POST["system_secret"] ?? null;
        $type = $_POST["type"] ?? null;

        $errors = validate_form($rules);

        if(!$errors){
            if($admin_register == 1 && empty($system_secret)){
                $errors["system_secret"] = "System secret is needed to activate it";
            }elseif($admin_register == 1 && !check_secret($system_secret)){
                $errors["system_secret"] = "System secret provided is not valid";
            }else{
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
     * This is used to get a message to be shown to the user upon deletion
     * @param string $table The name of the table
     * @return array Returns [succces => string, error => string]
     */
    function delete_message(string $table) :array{
        $message = [
            "success" => "Item has been deleted",
            "error" => "Failed to delete item from '$table'"
        ];

        switch($table){
            case "admins":
                $message["success"] = "Admin has been deleted";
                $message["error"] = "An error occured while deleting the admin";
                break;
            case "students":
                $message["success"] = "Student has been deleted";
                $message["error"] = "An error occured while deleting the student";
                break;
            case "teachers":
                $message["success"] = "Teacher has been deleted";
                $message["error"] = "An error occured while deleting the teacher";
                break;
            case "halls":
                $message["success"] = "Hall has been deleted";
                $message["error"] = "An error occured while deleting the hall";
                break;
            case "programs":
                $message["success"] = "Program has been deleted";
                $message["error"] = "An error occured while deleting the program";
                break;
            case "faculties":
                $message["success"] = "Faculty has been deleted";
                $message["error"] = "An error occured while deleting the faculty";
                break;
            case "departments":
                $message["success"] = "Department has been deleted";
                $message["error"] = "An error occured while deleting the department";
                break;
            case "courses":
                $message["success"] = "Course has been deleted";
                $message["error"] = "An error occurred while deleting the course";
                break;
        }

        return $message;
    }

    /**
     * This is used to generally delete an item from the database. It is basically used together with the delete item component
     */
    function delete_item(){
        global $errors;

        $id = $_POST["delete-id"] ?? null;
        $table = $_POST["delete-table"] ?? null;
        $column = $_POST["delete-column"] ?? "id";
        $message = delete_message($table);

        if (empty($id) || empty($table)) {
            $errors["system_message"] = "Invalid data provided for deletion";
        } else {
            // all users should be deleted from the users table
            $table = in_array($table, ["admins", "students", "teachers"]) ? "users" : $table;
            if (delete($table, "$column = $id")) {
                $_SESSION["system_message"] = $message["success"];
            } else {
                $errors["system_message"] = $message["error"];
            }
        }
    }

    /**
     * Generate a strong random password.
     * Includes uppercase, lowercase, numbers, and symbols.
     */
    function generate_random_password(int $length = 10): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+<>?';
        $password = '';
        $max_index = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max_index)];
        }

        return $password;
    }

    /**
     * Used to determine of the software is still in system setup mode
     * @return bool
     */
    function in_setup_mode() :bool{
        return boolval(session('admin_register') ?? false);
    }

    enum QUESTION_TYPES {
        case EVALUATION;
        case ASSESSMENT;
    }
    
    /**
     * Returns a list of question types handled by the software.
     *
     * @param QUESTION_TYPES $mode The mode of question you want
     * @param bool $id_only Returns only the ids. When false returns the full array with id/text
     * @return array
     */
    function get_question_types(QUESTION_TYPES $mode, bool $id_only = false): array
    {
        // Define all question types
        $types = [
            ["id" => "scale_5","text" => "Likert Scale (1 - 5)","for" => [QUESTION_TYPES::EVALUATION]],
            ["id" => "scale_10","text" => "Numeric Scale (1 - 10)","for" => [QUESTION_TYPES::EVALUATION]],
            ["id" => "select_single", "text" => "Single Choice (Radio/Dropdown)"],
            ["id" => "select_multiple", "text" => "Multiple Choice (Checkboxes)"],
            ["id" => "text_short","text" => "Short Text Input (Single Line)"],
            ["id" => "text_long","text" => "Long Text Input (Comment Box)"],
            ["id" => "boolean","text" => "Yes/No (Boolean)"]
        ];
    
        $result = [];
    
        foreach ($types as $type) {
            // Filter by 'for' key if it exists
            if (isset($type['for']) && !in_array($mode, $type['for'], true)) {
                continue;
            }
    
            // Only return ID if $id_only
            if ($id_only) {
                $result[] = $type['id'];
            } else {
                $result[] = [
                    "id" => $type['id'],
                    "text" => $type['text']
                ];
            }
        }
    
        return $result;
    }

    /**
     * A function that generates a unique code
     */
    function generate_unique_code(int $length = 8, bool $use_letters = true, bool $use_numbers = true, bool $use_special_chars = false): string {
        if ($length <= 0) {
            throw new InvalidArgumentException('Length must be greater than 0');
        }
    
        $letters  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $numbers  = '0123456789';
        $specials = '!@#$%^&*()_-+=<>?';
    
        $pool = '';
    
        if ($use_letters) {
            $pool .= $letters;
        }
    
        if ($use_numbers) {
            $pool .= $numbers;
        }
    
        if ($use_special_chars) {
            $pool .= $specials;
        }
    
        if ($pool === '') {
            throw new RuntimeException('No character set selected');
        }
    
        $code = '';
        $maxIndex = strlen($pool) - 1;
    
        for ($i = 0; $i < $length; $i++) {
            $code .= $pool[random_int(0, $maxIndex)];
        }
    
        return $code;
    }

    /**
     * Provides a list of disability types
     * @return array
     */
    function disability_types() :array{
        return [
            ["value" => "visual_impairment", "text" => "Visual Impairment"],
            ["value" => "hearing_impairment", "text" => "Hearing Impairment"],
            ["value" => "mobility_impairment", "text" => "Mobility Impairment"],
            ["value" => "learning_disability", "text" => "Learning Disability"],
            ["value" => "speech_impairment", "text" => "Speech Impairment"],
            ["value" => "autism_spectrum_disorder", "text" => "Autism Spectrum Disorder"],
            ["value" => "chronic_illness", "text" => "Chronic Illness"],
            ["value" => "mental_health_condition", "text" => "Mental Health Condition"],
            ["value" => "other", "text" => "Other"]
        ];
    }

    require_once "mailer_functions.php";
    require_once "jobs.php";
    require_once "student_function.php";
    require_once "form-validation.php";