<?php
    require_once $_SERVER["DOCUMENT_ROOT"]."/includes/session.php";

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        $_SESSION["old_input"] = $_REQUEST;

        if($submit == "set_password"){
            $password = $_POST["password"];
            $confirm_password = $_POST["confirm_password"];
            $user_id = $_POST["user_id"];
            $new_user = isset($_POST["new_user"]);

            if(empty($_POST["user_id"])){
                $errors["system_message"] = "User could not be defined or is invalid";
            }elseif(!is_numeric($_POST["user_id"])){
                $errors["system_message"] = "User defined is invalid";
            }if(empty($password)){
                $errors["password"] = "Password is required";
            }if(empty($confirm_password)){
                $errors["confirm_password"] = "Please confirm your password";
            }if($password !== $confirm_password){
                $errors["password"] = "Passwords do not match";
            }

            if(empty($errors)){
                $errors["system_message"] = "Not done yet. Try at a later time.";
            }
        }
    }else{
        $errors["system_message"] = "No submission provided";
    }

    if(isset($_REQUEST["response_type"]) && $_REQUEST["response_type"] == "json"){
        header("Content-type: application/json");
        echo json_encode([
            "errors" => $errors,
            "old_input" => $_REQUEST,
            "status" => $status ?? false,
            "message" => $message ?? null
        ]);
    }elseif($errors){
        $_SESSION["errors"] = $errors;
        header("location: $request_from");
    }elseif(!is_null($next_request)){
        unset($_SESSION["old_input"]);
        header("location: ".url($next_request));
    }else{
        unset($_SESSION["old_input"]);
        header("location: $request_from");
    }