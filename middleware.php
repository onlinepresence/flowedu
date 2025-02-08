<?php
    if($requestUri != "/shutdown")
        include_once "includes/session.php";

    function auth($next) {
        if (!isset($_SESSION['user_id'])) {
            // go to the login page
            $_SESSION["errors"]["system_message"] = "User authentication is required";
            header("location: /");
        }elseif(!isset($_SESSION["user"]) || time() - $_SESSION["last_fetch"] > 300){
            user(true);
        }

        $next(); // Proceed to the next handler
    }

    function check_school($next) {
        // check if a school exists
        $school = school();

        if(!$school){
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