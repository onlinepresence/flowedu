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
                        send_verification_email();
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
        }elseif($submit == "save_teacher" || $submit == "update_teacher"){
            $rules = [
                "user_id"          => "required|numeric|positive",
                "lastname"         => "required|string|max:50",
                "othernames"       => "required|string|max:255",
                "gender"           => "required|string",
                "date_of_birth"    => "required|date",
                "nationality"      => "required|string|max:255",
                "ghana_card"       => "required|string|ghana_card",
                "contact_address"  => "required|string|max:100",
                "phone_number"     => "required|phone",
                "staff_id"         => "required|string",
                "department_id"    => "nullable|integer",
                "rank"             => "nullable|string|max:30",
                "qualification"    => "nullable|string|max:20",
                "specialization"   => "required|string|max:50",
                "employment_type"  => "nullable|string|max:20",
                "years_experience" => "required|numeric|min:0|max:50",
                "emergency_name"   => "nullable|string|max:50",
                "emergency_phone"  => "nullable|phone",
                "research_interests" => "nullable|string|max:255",
                "cv" => "nullable|file|mimes:pdf,doc,docx|max:2048",
                "profile_pic" => "nullable|file|mimes:jpg,jpeg,png|max:1024",
                "id_document" => "nullable|file|mimes:pdf,doc,docx|max:2048",
                "certificate" => "nullable|file|mimes:pdf,doc,docx|max:2048",
                "date_of_appointment" => "required|date|before:tomorrow"
            ];

            $messages = [
                "user_id" => [
                    "required" => "User could not be defined or is invalid",
                    "numeric"  => "User defined is invalid"
                ]
            ];

            $errors = validate_form($rules, $messages);

            if(!$errors){
                $staff_id = $_POST["staff_id"];
                $data = form_data("teachers/$staff_id");

                if($submit == "update_teacher"){
                    $data["is_onboarded"] = true;
                }

                // if updating, remove optional files
                if($submit == "update_teacher"){
                    if(empty($_FILES["cv"]["name"])){
                        unset($data["cv"]);
                    }
                    if(empty($_FILES["profile_pic"]["name"])){
                        unset($data["profile_pic"]);
                    }
                    if(empty($_FILES["id_document"]["name"])){
                        unset($data["id_document"]);
                    }
                    if(empty($_FILES["certificate"]["name"])){
                        unset($data["certificate"]);
                    }
                }

                // update the teacher
                $response = update(user(), $data, "teachers", ["user_id"]);

                if($response){
                    if($submit === "save_teacher"){
                        update(user(), ["username" => $staff_id], "users", ["id"]);
                        $next_request = "teacher/dashboard";
                    }
                    
                    $_SESSION["system_message"] = ($submit === "update_teacher") ? "Your profile details have been updated successfully." : "Welcome aboard! Your information has been saved successfully.";

                    // refresh user session
                    user(true);
                }else{
                    if($submit == "save_teacher"){
                        unlink(asset($data["certificate"], false, true));
                        unlink(asset($data["profile_pic"], false, true));
                        unlink(asset($data["id_document"], false, true));
                        unlink(asset($data["cv"], false, true));
                    }
                }
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