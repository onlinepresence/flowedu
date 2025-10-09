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
            }elseif(empty($confirm_password)){
                $errors["confirm_password"] = "Please confirm your password";
            }elseif($password !== $confirm_password){
                $errors["password"] = "Passwords do not match";
            }elseif(($pass_error = is_valid_password($password)) !== true){
                $errors["password"] = $pass_error;
            }

            if(empty($errors)){
                $data = form_data(exclude: ["new_user", "confirm_password", "user_id"]);
                $data["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
                $response = update(user(), $data, "users", ["id"]);

                if($response === true){
                    if($new_user){
                        $data = [
                            "user_id" => $user_id, "password_reset_required" => false
                        ];
                        update(user(), $data, "teachers", ["user_id"]);

                        // update user session information
                        user(true);
                    }

                    $_SESSION["system_message"] = "Password has been reset";
                }else{
                    if(empty($_SESSION["errors"]["system_message"])){
                        $errors["system_message"] = is_string($response) ? $response : "User update could not be parsed";
                    }else{
                        $errors["system_message"] = $_SESSION["errors"]["system_message"];
                    }
                }
            }
        }elseif($submit == "save_teacher"){
            
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