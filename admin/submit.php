<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        $_SESSION["old_input"] = $_REQUEST;

        if($submit == "create_admin"){
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
                $data = form_data(exclude: ["username"]);
                $response = empty(user()["username"]) ? data_insert("admins", $data) : update(user(), $data, "admins", ["user_id"]);

                if($response === true){
                    $response = update(user(true), ["username" => $username], "users", ["id"]);

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
                $data = form_data("uploads/school/", ["school_id"]);
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
        }elseif($submit == "create_faculty"){
            $name = $_POST["name"] ?? null;

            if(empty($name)){
                $errors["name"] = "Faculty name is required";
            }

            if(empty($errors)){
                $data = form_data();

                if(data_insert("faculties", $data)){
                    $_SESSION["system_message"] = "Faculty '$name' has been added";
                }
            }
        }elseif($submit == "create_department"){
            $name = $_POST["name"] ?? null;
            $faculty_id = $_POST["faculty_id"] ?? null;
    
            if(empty($name)){
                $errors["name"] = "Name of department not provided";
            }if(!empty($faculty_id) && !is_numeric($faculty_id)){
                $errors["faculty_id"] = "Faculty provided is invalid or incorrect";
            }
    
            if(empty($errors)){
                $data = form_data(exclude: empty($faculty_id) ? ["faculty_id"] : []);
                if(data_insert("departments", $data)){
                    $_SESSION["system_message"] = "Department '$name' has been added";
                }
            }
        }elseif($submit == "create_program"){
            if(empty($_POST["name"])){
                $errors["name"] = "Name of program is required";
            }if(empty($_POST["cost"])){
                $errors["cost"] = "Cost fee of program is required";
            }if(empty($_POST["certificate"])){
                $errors["certificate"] = "Program certificate of completion is required";
            }
            if(empty($_POST["department_id"])){
                $errors["department_id"] = "No department has been selected";
            }elseif(!is_numeric($_POST["department_id"])){
                $errors["department_id"] = "Chosen department is invalid";
            }

            if(!$errors){
                $data = form_data();
                if(data_insert("programs", $data)){
                    $_SESSION["system_message"] = "Program '{$_POST['name']}' has been added";
                }
            }
        }elseif($submit == "create_hall"){
            if(empty($_POST["name"])){
                $errors["name"] = "Name of hall is required";
            }if(empty($_POST["cost"])){
                $errors["cost"] = "Please specify the cost per head";
            }elseif(!is_numeric($_POST["cost"])){
                $errors["cost"] = "Invalid cost value provided";
            }
            if(empty($_POST["period"])){
                $errors["period"] = "Please specify the cost duration";
            }elseif(!in_array($_POST["period"], ["per_semester", "per_year"])){
                $errors["period"] = "Invalid cost duration provided";
            }

            if(!$errors){
                if(data_insert("halls", form_data())){
                    $_SESSION["system_message"] = "The hall '{$_POST['name']}' has been added";
                }
            }
        }elseif($submit == "change_school_status"){
            if(update(school(), form_data(), "schools", ["id"])){
                $_SESSION["system_message"] = "Settings have been updated";
                unset($_SESSION["admin_register"]);
                $next_request = "/admin/dashboard";

                // send activation email
                add_job("email", create_payload("send_email", [
                    "message" => "Your school account has been ".($_POST["ready"] == 1 ? "activated" : "deactivated"),
                    "receipients" => user()["email"], "subject" => "School status change"
                ]));
            }
        }elseif($submit == "fetch_user"){
            if(empty($_GET["id"])){
                $errors["system_message"] = "User id is not valid";
            }else{
                $data = get_user_details($_GET["id"], $_GET["type"] ?? null);
                $status = !empty($data);

                if(isset($_GET["type"]) && $_GET["type"] == "student"){
                    $guardian = fetchData("name, relationship, address, phone_number, email", "parent_guardians", "student_id={$data['student_id']}");
                    $data = ["student" => $data, "guardian" => $guardian];
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
            "data" => $data ?? null
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