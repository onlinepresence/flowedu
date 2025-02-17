<?php
    $last_request = $_SERVER["HTTP_REFERER"];

    // user authentication is been handled by the auth middlware
    if(!send_verification_email()){
        $_SESSION["errors"]["system_message"] = "Verification could not be sent. User authentication might be required";
    }else{
        $_SESSION["system_message"] = "Email verification has been sent to you";
    }

    header("location: $last_request");