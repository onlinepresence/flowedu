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
            }if(empty($_POST["ghana_card"])){
                $errors["ghana_card"] = "Ghana card number is required";
            }elseif(!is_valid_ghana_card_number($_POST["ghana_card"])){
                $errors["ghana_card"] = "Invalid Ghana card provided";
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

                    if($type == 2){
                        $next_request = "admin/dashboard";
                        send_verification_email();
                    }
                }
            }
        }elseif($submit == "add_user"){
            $email = $_POST["email"] ?? null;
            $type = $_POST["type"] ?? null;
            $password = $_POST["password"] ?? null;

            $rules = [
                "email" => "required|string|email",
                "type" => "required",
                "password" => "nullable|string|password",
                "staff_id" => "nullable"
            ];

            $messages = [
                "type" => [
                    "required" => "User Type is required"
                ]
            ];

            $errors = validate_form($rules, $messages);

            // other validations
            if(!$errors){
                if (empty($password)) {
                    if ($type == "teacher") {
                        // Generate a random secure password
                        $password = generate_random_password(10);
                    } else {
                        $errors["password"] = "Password is required";
                    }
                }elseif($type == "teacher"){
                    // alert teacher to not change password
                    $_POST["password_reset_required"] = 0;
                }
            }            

            if(!$errors){
                $data = [
                    "email" => $email,
                    "type" => in_array($type, [2,3,4]) ? "admin" : $type,
                    "password" => password_hash($password, PASSWORD_DEFAULT),
                    "user_secret" => generate_user_secret()
                ];

                if($data["type"] == "teacher" && !empty($_POST["staff_id"])){
                    $data["username"] = $_POST["staff_id"];
                }

                if(data_insert("users", $data)){
                    // add user to table
                    if($data["type"] == "admin"){
                        data_insert("admins", [
                            "user_id" => $connect->insert_id,
                            "type" => $type
                        ]);
                    }elseif($data['type'] == "teacher"){
                        data_insert("teachers", [
                            "user_id" => $connect->insert_id,
                            "staff_id" => $_POST["staff_id"] ?? null,
                        ]);
                    }

                    $details = [
                        "email" => $email, "password" => $password
                    ];
                    
                    send_account_created_email($email, $details ?? null);
                    $_SESSION["system_message"] = ucfirst($data["type"])." account has been added";
                }else{
                    $errors["system_message"] = "User account could not be added";
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
        }
        
        // faculty related items
        elseif($submit == "create_faculty"){
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
        }elseif($submit == "update_faculty"){
            $faculty_id = $_POST["faculty_id"] ?? null;
            $name = $_POST["name"] ?? null;
            $dean_id = $_POST["dean_id"] ?? null;

            if(empty($faculty_id)){
                $errors["faculty_id"] = "Faculty id is not valid";
            }if(empty($name)){
                $errors["name"] = "Faculty name is required";
            }if(!empty($dean_id) && !is_numeric($dean_id)){
                $errors["dean_id"] = "Invalid faculty dean provided";
            }

            if(!$errors){
                $data = form_data(key_change: ["faculty_id" => "id"], exclude: empty($dean_id) ? ["dean_id"] : []);
                $faculty = faculties($faculty_id);

                if(update($faculty, $data, "faculties", ["id"])){
                    $_SESSION["system_message"] = "Faculty '{$faculty['name']}' has been updated";
                }
            }
        }

        // department related items
        elseif($submit == "create_department"){
            $name = $_POST["name"] ?? null;
            $faculty_id = $_POST["faculty_id"] ?? null;
            $hod = $_POST["hod"] ?? null;
    
            if(empty($name)){
                $errors["name"] = "Name of department not provided";
            }if(!empty($faculty_id) && !is_numeric($faculty_id)){
                $errors["faculty_id"] = "Faculty provided is invalid or incorrect";
            }if(!empty($hod) && !is_numeric($hod)){
                $errors["hod"] = "Head of Department provided is invalid or incorrect";
            }
    
            if(empty($errors)){
                $exclude = [];

                if(empty($faculty_id)){
                    $exclude[] = "faculty_id";
                }
                
                if(empty($hod)){
                    $exclude[] = "hod";
                }

                $data = form_data(exclude: $exclude);
                if(data_insert("departments", $data)){
                    $_SESSION["system_message"] = "Department '$name' has been added";
                }
            }
        }elseif($submit == "update_department"){
            $department_id = $_POST["department_id"] ?? null;
            $name = $_POST["name"] ?? null;
            $faculty_id = $_POST["faculty_id"] ?? null;

            if(empty($department_id)){
                $errors["department_id"] = "Department id is not valid";
            }if(empty($name)){
                $errors["name"] = "Name of department not provided";
            }if(!empty($faculty_id) && !is_numeric($faculty_id)){
                $_SESSION["system_message"] = "Faculty provided is invalid or incorrect";
            }

            if(!$errors){
                $exclude = [];

                if(empty($faculty_id)){
                    $exclude[] = "faculty_id";
                }
                
                if(empty($hod)){
                    $exclude[] = "hod";
                }
                $data = form_data(key_change: ["department_id" => "id"], exclude: $exclude);
                $department = departments($department_id);

                if(update($department, $data, "departments", ["id"])){
                    $_SESSION["system_message"] = "Department '{$department['name']}' has been updated";
                }
            }
        }

        // program management
        elseif($submit == "create_program"){
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
                $data = form_data(exclude: ["program_id"]);
                if(data_insert("programs", $data)){
                    $_SESSION["system_message"] = "Program '{$_POST['name']}' has been added";
                }
            }
        }elseif($submit == "update_program"){
            $program_id = $_POST["program_id"] ?? null;
            $name = $_POST["name"] ?? null;
            $cost = $_POST["cost"] ?? null;
            $department_id = $_POST["department_id"] ?? null;

            if(empty($program_id)){
                $errors["program_id"] = "Program id is not valid";
            }if(empty($name)){
                $errors["name"] = "Name of program is required";
            }if(empty($cost)){
                $errors["cost"] = "Cost fee of program is required";
            }if(empty($department_id)){
                $errors["department_id"] = "No department has been selected";
            }elseif(!is_numeric($department_id)){
                $errors["department_id"] = "Chosen department is invalid";
            }

            if(!$errors){
                $data = form_data(key_change: ["program_id" => "id"]);
                $program = programs($program_id);

                if(update($program, $data, "programs", ["id"])){
                    $_SESSION["system_message"] = "Program '{$program['name']}' has been updated";
                }
            }
        }

        // course management
        elseif($submit == "create_course"){
            if(empty($_POST["name"])){
                $errors["name"] = "Name of course is required";
            }if(empty($_POST["course_semester"])){
                $errors["course_semester"] = "Course Semester is required";
            }elseif(!is_numeric($_POST["course_semester"])){
                $errors["course_semester"] = "Invalid course semster value";
            }if(empty($_POST["program_id"])){
                $errors["program_id"] = "No program has been selected";
            }elseif(!is_numeric($_POST["program_id"])){
                $errors["program_id"] = "Chosen program is invalid";
            }if(empty($_POST["year_level"])){
                $errors["year_level"] = "Year level is required";
            }elseif(!is_numeric($_POST["year_level"])){
                $errors["year_level"] = "Invalid year level value";
            }

            if(empty($_POST["code"])){
                $_REQUEST["code"] = create_course_code($_POST["program_id"], $_POST["year_level"], $_POST["course_semester"]);

                if($_REQUEST["code"] === false){
                    $errors["system_message"] = "Code could not be generated";
                }
            }

            if(!$errors){
                $data = form_data(exclude: ["course_id"]);
                if(data_insert("courses", $data)){
                    $_SESSION["system_message"] = "Course '{$_POST['name']}' has been added";
                }
            }
        }elseif($submit == "update_course"){
            $course_id = $_POST["course_id"] ?? null;
            $name = $_POST["name"] ?? null;
            $code = $_POST["code"] ?? null;
            $course_semester = $_POST["course_semester"] ?? null;
            $year_level = $_POST["year_level"] ?? null;
            $program_id = $_POST["program_id"] ?? null;

            if(empty($course_id)){
                $errors["course_id"] = "Course id is not valid";
            }if(empty($name)){
                $errors["name"] = "Name of course is required";
            }if(empty($code)){
                $errors["code"] = "Course code is required";
            }if(empty($course_semester)){
                $errors["course_semester"] = "Course Semester is required";
            }elseif(!is_numeric($course_semester)){
                $errors["course_semester"] = "Invalid course semster value";
            }if(empty($year_level)){
                $errors["year_level"] = "Year level is required";
            }elseif(!is_numeric($year_level)){
                $errors["year_level"] = "Invalid year level value";
            }if(empty($program_id)){
                $errors["program_id"] = "No program has been selected";
            }elseif(!is_numeric($program_id)){
                $errors["program_id"] = "Chosen program is invalid";
            }

            if(!$errors){
                $data = form_data(exclude: ["name", "code"], key_change: ["course_id" => "id"]);
                $course = courses($course_id);

                if(update($course, $data, "courses", ["id"])){
                    $_SESSION["system_message"] = "Course '{$course['name']}' has been updated";
                }
            }
        }

        // delete an item
        elseif($submit == "delete-item"){
            delete_item();
        }

        else{
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