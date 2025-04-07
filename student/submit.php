<?php
    require_once $_SERVER["DOCUMENT_ROOT"]."/includes/session.php";

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        $_SESSION["old_input"] = $_REQUEST;

        if($submit == "create_student"){
            require_once "$rootPath/includes/image_validation.php";

            if(empty($_POST["user_id"])){
                $errors["system_message"] = "User could not be defined or is invalid";
            }elseif(!is_numeric($_POST["user_id"])){
                $errors["system_message"] = "User defined is invalid";
            }if(empty($_POST["index_number"])){
                $errors["index_number"] = "Index number could not be generated";
            }if(empty($_POST["lastname"])){
                $errors["lastname"] = "Please provide your lastname";
            }if(empty($_POST["othernames"])){
                $errors["othernames"] = "Please provide your othername(s)";
            }if(empty($_POST["date_of_birth"])){
                $errors["date_of_birth"] = "Date of birth is required";
            }if(empty($_POST["gender"])){
                $errors["gender"] = "Gender is required";
            }if(!in_array($_POST["gender"], ["male", "female"])){
                $errors["gender"] = "Gender provided is invalid";
            }if(empty($_POST["nationality"])){
                $errors["nationality"] = "Nationality is required";
            }if(empty($_POST["insurance_number"])){
                $errors["insurance_number"] = "Your insurance number is required";
            }elseif(fetchData("insurance_number", "students", "insurance_number='{$_POST['insurance_number']}' AND user_id != {$_POST['user_id']}")){
                $errors["insurance_number"] = "Insurance number already exists";
            }if(empty($_POST["ghana_card"])){
                $errors["ghana_card"] = "Ghana card number is required";
            }elseif(!is_valid_ghana_card_number($_POST["ghana_card"])){
                $errors["ghana_card"] = "Invalid Ghana card provided";
            }if(empty($_POST["program_id"])){
                $errors["program_id"] = "Program is required";
            }elseif(!is_numeric($_POST["program_id"])){
                $errors["program_id"] = "Invalid program has been provided";
            }elseif(empty($_POST["department_id"])){
                $errors["program_id"] = "Program department could not be identified";
            }elseif(!is_numeric($_POST["department_id"])){
                $errors["program_id"] = "Program department is invalid";
            }if(empty($_POST["hall_id"])){
                $errors["hall_id"] = "Hall is required";
            }elseif(!is_numeric($_POST["hall_id"])){
                $errors["hall_id"] = "Invalid hall has been selected";
            }if(empty($_POST["username"])){
                $errors["username"] = "Username is required";
            }elseif(fetchData("username", "users", "username='{$_POST['username']}' AND id != {$_POST['user_id']}")){
                $errors["username"] = "Username already exists";
            }if(empty($_POST["contact_address"])){
                $errors["contact_address"] = "Contact address is required";
            }if(empty($_POST["phone_number"])){
                $errors["phone_number"] = "Phone number is required";
            }if(empty(user()["username"]) && empty($_FILES["profile_pic"]["name"])){
                $errors["profile_pic"] = "Profile picture is required";
            }

            $validate_profile = validate_passport_photo($_FILES["profile_pic"]["tmp_name"]);
            if(!$validate_profile["status"]){
                $errors["profile_pic"] = $validate_profile["message"];
            }
            
            if(!$errors){
                $data = form_data("students/profiles/", ['username', 'prev_profile_pic']);
                $response = user()["username"] ? update(user(), $data, "students", ["user_id"]) : data_insert("students", array_merge($data, ["admission_index" => $data["index_number"]]));

                if($response){
                    // remove old picture
                    if(!empty($data["profile_pic"]))
                        reset_profile_pic();
                    
                    $response = update(user(), ["username" => $_POST["username"]], "users", ["id"]);
                    if($response === true){
                        $_SESSION["system_message"] = empty(user()["username"]) ? "Your account details have been saved" : "Changes have been applied";
                        user(true);
                    }else{
                        if(empty($_SESSION["errors"]["system_message"])){
                            $errors["system_message"] = is_string($response) ? $response : "User update could not be parsed";
                        }else{
                            $errors["system_message"] = $_SESSION["errors"]["system_message"];
                        }
                    }
                }else{
                    // remove stored file
                    unlink(asset($data["profile_pic"], false, true));
                }
            }
        }elseif($submit == "save_guardian"){
            if(empty($_POST["name"])){
                $errors["name"] = "Guardian name is required";
            }if(empty($_POST["student_id"])){
                $errors["system_message"] = "Student data could not be verified";
            }if(empty($_POST["relationship"])){
                $errors["relationship"] = "Relationship with guardian is required";
            }if(empty($_POST["phone_number"])){
                $errors["phone_number"] = "Guardian phone number is required";
            }

            if(!$errors){
                $data = form_data(exclude: ["id"]);
                $response = $_POST["id"] > 0 ? update(guardian(), $data, "parent_guardians", ["id"]) : data_insert("parent_guardians", $data);

                if($response === true){
                    $_SESSION["system_message"] = $_POST["id"] > 0 ? "Changes have been saved" : "Guardian information has been added";
                }
            }
        }elseif($submit == "change_status"){
            if(!is_numeric($_POST["is_new"]) && empty($_POST["is_new"])){
                $errors["system_message"] = "New status is invalid -> {$_POST['is_new']}";
            }elseif(!guardian()){
                $errors["system_message"] = "Guardian information not provided";
            }elseif(is_null(user()['approved'])){
                $errors["system_message"] = "Admission details have not been submitted yet.";
            }elseif(!user()['approved']){
                $errors["system_message"] = "Your admission has not yet been approved. Please check at another time";
            }else{
                $data = form_data(preserve:["is_new"]);
                // change the index number
                $data["index_number"] = create_index_number();

                $user = user();
                $user["id"] = $user["student_id"];

                if(($response = update($user, $data, "students", ["id"]))){
                    $next_request = "student/dashboard";
                    $_SESSION["system_message"] = "Admission process successfully completed";

                    // refresh user state
                    user(true);
                }else{
                    if(!empty($_SESSION["errors"]["system_message"]))
                        $errors["system_message"] = is_string($response) ? $response : "Error occured while updating user profile";
                }
            }
        }elseif($submit == "delete-account"){
            if(empty($_POST["user_id"])){
                $errors["system_message"] = "User was not defined or could not be accessed";
            }elseif(!is_numeric($_POST["user_id"])){
                $errors["system_message"] = "Invalid user provided";
            }else{
                $data = form_data(key_change:["user_id" => "id"], preserve: ["id"]);

                if(delete("users", create_where_from_array($data))){
                    send_to_next_request();
                    $_SESSION["system_message"] = "Account has been deleted";
                    $next_request = "/logout";
                }else{
                    $errors["system_message"] = "Registration could not be canceled";
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