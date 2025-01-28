<?php
    if($requestUri != "/shutdown")
        include_once "includes/session.php";

    function auth($next) {
        if (!isset($_SESSION['user_id'])) {
            // go to the login page
            $_SESSION["errors"]["message"] = "User authentication is required";
            header("location: /");
        }elseif(!isset($_SESSION["user"]) || time() - $_SESSION["last_fetch"] > 300){
            user(true);
        }

        $next(); // Proceed to the next handler
    }

    function check_school($next) {
        // check if a school exists
        $school = fetchData("id", "schools", limit: 1);

        if(!$school){
            $_SESSION["admin_register"] = true;
            header("location: /register");
        }

        $next();
    }