<?php
    if(!in_array($requestUri, ["/shutdown", "/env-generator"]))
        include_once "includes/session.php";

    function auth($next) {
        if (!isset($_SESSION['user_id'])) {
            // go to the login page
            $_SESSION["errors"]["system_message"] = "User authentication is required";
            header("location: /");
        }elseif(!isset($_SESSION["user"]) || time() - $_SESSION["last_fetch"] > 300){
            user(true);
        }

        // prevent further autorization if user is locked
        if(!user()['active']){
            session_unset();
            $_SESSION["errors"]["system_message"] = "Your account has been deactivated";
            header("location: /");
        }

        $next(); // Proceed to the next handler
    }

    function check_school($next) {
        // check if a school exists
        $school = school();

        if(!$school){
            // check if there is a user in the system and delete
            if($user = fetchData("id", "users")){
                delete("users", "id={$user['id']}");
            }
            $_SESSION["admin_register"] = true;
            header("location: /register");
        }

        $next();
    }

    function check_school_status($next){
        $next_request = null;
        if(!school()["ready"]){
            $next_request = $_SESSION["user_type"] == "owner" || $_SESSION["user_type"] == "admin" ? "/admin-setup/school" : "/";
            if($next_request == '/'){
                session_unset();
            }else{
                $_SESSION["admin_register"] = true;
            }
        }

        if($next_request){
            $_SESSION["system_warning"] = "School is not ready for use";
            send_to_next_request();
            header("location: $next_request");
        }

        $next();
    }

    function check_departments($next){
        if(!departments()){
            $_SESSION["errors"]["system_message"] = "No active departments created. Programs cannot be added";
            send_to_next_request();
            header("location: /admin-setup/departments");
        }
        $next();
    }

    function student_ready($next){
        if(is_null(user()['approved'])){
            $_SESSION["errors"]["system_message"] = "Admission form submission not completed";
            header("location: ".url("student-setup/personal"));
            send_to_next_request();
        }elseif(!user()["approved"]){
            $_SESSION["errors"]["system_message"] = "Your admission is yet to be approved";
            header("location: ".url("student-setup/personal"));
            send_to_next_request();
        }elseif(user()["is_new"] && user()["approved"]){
            $_SESSION["system_warning"] = "Admission has been approved. Activate your dashboard to proceed";
            header("location: ".url("student-setup/status"));
            send_to_next_request();
        }

        $next();
    }

    function admission_is_open($next){
        $school = school();

        if(!isset($_SESSION["admin_register"]) && !$school["is_admit"]){
            $_SESSION["system_warning"] = "School is not receiving new students";
            send_to_next_request();
            header("location: /");
        }

        $next();
    }

    function valid_admin($next){
        $user = user();

        if(empty($user["username"])){
            $_SESSION["errors"]["system_message"] = "Complete your user profile to proceed";
            send_to_next_request();
            header("location: /admin-setup/personal");
        }

        $next();
    }

    function valid_teacher($next){
        $user = user();

        if(!$user["is_onboarded"]){
            $_SESSION["errors"]["system_message"] = "Complete your user profile to proceed";
            send_to_next_request();
            header("location: /teacher/setup");
        }

        $next();
    }

    function valid_teacher_check($next){
        $user = user();

        if($user["is_onboarded"]){
            header("location: /teacher/dashboard");
        }

        $next();
    }