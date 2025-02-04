<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
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
                    $password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param("sss", $email, $password, $type);
                    
                    if($stmt->execute()){
                        // create the user session
                        create_user_session($type, $connect->insert_id);
                    }else{
                        throw new Exception($stmt->error);
                    }
                }catch(\Throwable $th){
                    $message = throwableMessage($th);
                    $errors["system_message"] = $message;
                }
            }

            if($admin_register == 1){
                $next_request = "admin-setup/personal";
            }else{
                $next_request = "student-setup/personal";
            }
        }elseif($submit == "create_admin"){
            $user_id = $_POST["user_id"] ?? null;
            $username = $_POST["username"] ?? null;
            $lastname = $_POST["lastname"] ?? null;
            $othernames = $_POST["othernames"] ?? null;
            $type = $_POST["type"] ?? $_SESSION["user_type"];

            if(empty($user_id)){
                $errors["user_id"] = "Admin account could not be found";
            }if(!is_numeric($user_id)){
                $errors["user_id"] = "Invalid admin account detected";
            }if(empty($username)){
                $errors["username"] = "Username is required";
            }if(empty($lastname)){
                $errors["lastname"] = "Lastname is required";
            }if(empty($othernames)){
                $errors["othernames"] = "Othername(s) is required";
            }if(empty($type)){
                $errors["system_message"] = "Admin type could not be detected";
            }

            if(!$errors){
                $data = form_data();
                $response = empty(user()["username"]) ? data_insert("admins", $data) : update(user(), $data, "admins", ["id"]);

                if($response === true){
                    $response = update(user(true), ["username" => $data["username"]], "users", ["id"]);

                    if($response === true){
                        $_SESSION["system_message"] = "Admin account updated";
                        user(true);     // reflect new changes
                    }
                }
            }
        }elseif($submit == "setup_school"){
            $school_id = $_POST["school_id"] ?? null;
            $name = $_POST["name"] ?? null;
            $address = $_POST["address"] ?? null;
            $email = $_POST["email"] ?? null;
            $phone = $_POST["phone"] ?? null;
            $website = $_POST["website"] ?? null;
            $description = $_POST["description"] ?? null;

            if(empty($name)){
                $errors["name"] = "School name is required";
            }if(empty($address)){
                $errors["address"] = "Address is required";
            }

            if(empty($errors)){
                $data = form_data("assets/uploads/school", ["school_id"]);
                if($school_id > 0){
                    $response = update(school(), $data, "schools", ["id"]);
                    $message = "School details have been updated";
                }else{
                    $response = data_insert("schools", $data);
                    $message = "School details have been added";
                }

                if($response){
                    $_SESSION["system_message"] = $message;
                }
            }
        }else{
            $errors["system_message"] = "Submission value '$submit' not accepted";
        }
    }else{
        $errors["system_message"] = "No submission provided";
    }

    if($_REQUEST["response_type"] == "json"){
        header("Content-type: application/json");
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