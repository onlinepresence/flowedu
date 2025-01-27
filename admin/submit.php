<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $_SESSION["old_input"] = $_REQUEST;

        if($submit == "create_account"){
            $email = $_POST["email"] ?? null;
            $password = $_POST["password"] ?? null;
            $password_confirm = $_POST["password_confirm"] ?? null;
            $type = $_POST["type"] ?? null;
            $admin_register = $_POST["admin_register"] ?? null;
            $system_secret = $_POST["system_secret"] ?? null;
            $errors = [];

            if(empty($email)){
                $errors["email"] = "No email provided";
            }if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
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
                $sql = "INSERT INTO users (email, password, type) VALUES (?,?,?)";
                try{
                    $stmt = $connect->prepare($sql);
                    $stmt->bind_param("sss", $email, $password, $type);
                    
                    if($stmt->execute()){
                        // create the user session
                        create_user_session($type, $connect->insert_id);
                    }else{
                        throw new Exception($stmt->error);
                    }
                }catch(\Throwable $th){
                    $message = throwableMessage($th);
                    $errors["message"] = $message;
                }
            }

            if($errors){
                $_SESSION["errors"] = $errors;
                header("location: $request_from");
            }elseif($admin_register == 1){
                // create a login session
                header("location: ".url("admin/personal"));
            }else{
                header("location: ".url("student/personal"));
            }


        }
    }else{
        $message = "No submission provided";
    }

    if($_REQUEST["ajax"]){
        header("Content-type: application/json");
    }

    echo $message;