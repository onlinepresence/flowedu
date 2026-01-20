<?php
    require_once $_SERVER["DOCUMENT_ROOT"]."/includes/session.php";

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        $_SESSION["old_input"] = $_REQUEST;

        if($submit == "create_student" || $submit == "update_student"){
            if($submit == "create_student")
                require_once "$rootPath/includes/image_validation.php";

            $rules = [
                "user_id" => "required|numeric",
                "index_number" => "required",
                "lastname" => "required|string",
                "firstname" => "required|string",
                "othernames" => "nullable|string",
                "date_of_birth" => "required|date",
                "nationality" => "required|string",
                "insurance_number" => "nullable|numeric",
                "ghana_card" => "required|string|ghana_card",
                "contact_address" => "required|string",
                "phone_number" => "required|string|phone|unique:students,phone_number,user_id != {$_POST['user_id']}",
                "religion" => "nullable|string",
                "denomination" => "nullable|string",
                "disability_status" => "nullable|string",
                "disability_type" => "nullable|required_if:disability_status,yes|string",
            ];
            
            // other creation account validations
            if($submit == "create_student"){
                $rules = array_merge($rules, [
                    "program_id" => "required|numeric|exists:programs,id",
                    "hall_id" => "required|numeric|exists:halls,id",
                    "username" => "required|unique:users,username,id != {$_POST['user_id']}",
                    "profile_pic" => "nullable|file|mimes:jpg,png,jpeg,avif,webp",
                    "gender" => "required|string|in:male,female"
                ]);

                $validate_profile = validate_passport_photo($_FILES["profile_pic"]["tmp_name"]);
                if(!$validate_profile["status"]){
                    $errors["profile_pic"] = $validate_profile["message"];
                }
            }elseif($submit == "update_student"){
                // verify account numbers
                $rules = array_merge($rules, [
                    "account_bank" => "nullable|required_if:account_number",
                    "account_number" => "nullable|required_if:account_bank|unique:students,account_number, user_id != ".user()['user_id'],
                    "ssnit_number" => "nullable|numeric",
                ]);
            }

            $errors = validate_form($rules);
            
            if(!$errors){
                $data = form_data("students/profiles/", ['username', 'prev_profile_pic']);

                if($submit == "update_student"){
                    $data["username"] = $data["index_number"];
                }

                $response = user()["username"] || $submit == "update_student" ? update(user(), $data, "students", ["user_id"]) : data_insert("students", array_merge($data, ["admission_index" => $data["index_number"]]));

                if($response){
                    // remove old picture
                    if($submit == "create_student"){
                        if(!empty($data["profile_pic"]))
                            reset_profile_pic();
                    }                    
                    
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
            $rules = [
                "name" => "required|string",
                "student_id" => "required|integer|positive|exists:students,id",
                "relationship" => "required|string",
                "phone_number" => "required|phone"
            ];
            $hidden = ["student_id"];
            $messages = [
                "student_id" => [
                    "required" => "Student Data could not be verified",
                    "exists" => "Student specified was not found"
                ]
            ];
            $errors = validate_form($rules, $messages, hidden: $hidden);

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
        }elseif($submit == "change_picture"){
            require_once "$rootPath/includes/image_validation.php";

            $validate_profile = validate_passport_photo($_FILES["profile_pic"]["tmp_name"]);
            if(!$validate_profile["status"]){
                $errors["profile_pic"] = $message = $validate_profile["message"];
            }

            if(empty($errors)){
                $data = form_data("students/profiles/", ['username', 'prev_profile_pic']);
                $response = update(user(), $data, "students", ["user_id"]);
                if($response){
                    // remove old picture
                    if(!empty($data["profile_pic"]))
                        reset_profile_pic();

                    $_SESSION["system_message"] = empty(user()["username"]) ? "Your account details have been saved" : "Changes have been applied";
                    user(true);
                    $message = "Profile Picture has been updated";
                    
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
        }else{
            $errors["system_message"] = "Submission value '$submit' not accepted";
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