<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        $_SESSION["old_input"] = $_REQUEST;

        if($submit == "create_admin" || $submit == "update_admin"){
            $user_id = $_POST["user_id"] ?? null;
            $username = $_POST["username"] ?? null;
            $lastname = $_POST["lastname"] ?? null;
            $othernames = $_POST["othernames"] ?? null;
            $type = $_POST["type"] ?? $_SESSION["user_type"];
            
            $rules = [
                "user_id" => "required|numeric|integer|positive|exists:users,id",
                "username" => "required|string|unique:users,username,username IS NOT NULL,id != $user_id",
                "lastname" => "required|string",
                "othernames" => "required|string",
                "ghana_card" => "required|string|ghana_card"
            ];

            if($submit == "create_admin"){
                $extra_rules = [
                    "type" => "required", 
                ];
            }elseif($submit == "update_admin"){
                $extra_rules = [
                    "profile_pic" => "nullable|file|accepts:jpg,jpeg,png,gif",
                    "gender" => "nullable|string|in:male,female",
                    "phone_number" => "required|string|phone|unique:admins,phone_number,phone_number IS NOT NULL,user_id != $user_id",
                    "position_title" => "required|string|",
                    "type" => "required|integer|exists:admin_types,id",
                    "department_id" => "required_if:type,3",
                    "faculty_id" => "required_if:type,4",
                    "date_of_appointment" => "nullable|date",
                ];
            }

            $rules = array_merge($rules, $extra_rules);

            $messages = [
                "user_id" => [
                    "required" => "Admin account could not be found",
                    "numeric" => "Invalid admin account detected",
                    "exists" => "Admin account does not exist",
                ],
            ];

            $alias = [
                "type" => "Admin Type",
                "department_id" => "Department",
                "faculty_id" => "Faculty"
            ];

            $errors = validate_form($rules, $messages, alias: $alias);

            if(!$errors){
                $data = form_data("admins/profile", exclude: ["username"]);
                $response = empty(user()["username"]) ? data_insert("admins", $data) : update(user(), $data, "admins", ["user_id"]);             

                if($response === true){
                    if($submit === "create_admin"){
                        $response = update(user(true), ["username" => $username], "users", ["id"]);
                    }

                    if($response === true){
                        $_SESSION["system_message"] = "Admin account updated";
                        user(true);     // reflect new changes
                    }

                    if($type == 2 && $submit === "create_admin"){
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

            $alias = [
                "type" => "User Type"
            ];

            $errors = validate_form($rules, alias: $alias);

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
                    "type" => is_numeric($type) ? "admin" : $type,
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
            $rules = [
                "name" => "string|name|unique:halls,name",
                "cost" => "required|numeric|positive",
                "period" => "required|string"
            ];
            $alias = [
                "name" => "Name of Hall",
                "cost" => "Cost per head",
                "period" => "Cost Duration"
            ];

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

            $rules = [
                "name" => "required|string|unique:faculties,name"
            ];
            
            $alias = [
                "name" => "Faculty Name"
            ];
            
            $errors = validate_form($rules, alias: $alias);

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

            $rules = [
                "faculty_id" => "required|integer|exists:faculties,id",
                "name" => "required|string|unique:faculties,name,id != $faculty_id",
                "dean_id" => "nullable|integer|exists:admins,id,type = 4"
            ];
            $alias = [
                "name" => "Faculty Name",
                "dean_id" => "Faculty Dean"
            ];

            $errors = validate_form($rules, alias: $alias);

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

            $rules = [
                "name" => "required|string|unique:departments,name",
                "faculty_id" => "nullable|integer|exists:faculties,id",
                "hod" => "nullable|integer|exists:admins,id,type = 3"
            ];

            $alias = [
                "name" => "Name of Department",
                "faculty_id" => "Faculty",
                "hod" => "Head of Department"
            ];

            $errors = validate_form($rules, alias: $alias);
    
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

            $rules = [
                "department_id" => "required|integer|exists:departments,id",
                "name" => "required|string|unique:departments,name,id != $department_id",
                "faculty_id" => "nullable|integer|exists:faculties,id"
            ];
            
            $alias = [
                "department_id" => "Department",
                "name" => "Department name",
                "faculty_id" => "Faculty"
            ];

            $errors = validate_form($rules, alias: $alias);

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
            $rules = [
                "name" => "required|string|unique:programs,name",
                "cost" => "required|numeric|positive",
                "certificate" => "required|string",
                "department_id" => "required|integer|exists:departments,id"
            ];
            $alias = [
                "name" => "Program Name",
                "cost" => "Cost Fee",
                "certificate" => "Program Certification",
                "department_id" => "Department"
            ];

            $errors = validate_form($rules, alias: $alias);

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

            $rules = [
                "program_id" => "required|integer|exists:programs,id",
                "name" => "required|string|unique:programs,name,id != $program_id",
                "cost" => "required|numeric|positive",
                "department_id" => "required|integer|exists:departments,id"
            ];
            $alias = [
                "program_id" => "Program",
                "name" => "Program Name",
                "cost" => "Cost Fee",
                "department_id" => "Department"
            ];

            $errors = validate_form($rules, alias: $alias);

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
            $rules = [
                "name" => "required|string|unique:courses,name",
                "course_semester" => "required|integer|positive|max:2|min:1",
                "program_id" => "required|integer|exists:programs,id",
                "year_level" => "required|integer|positive",
                "code" => "nullable|string|unique:courses,code"
            ];

            $alias = [
                "name" => "Course Name",
                "course_semester" => "Course Semester",
                "program_id" => "Program",
                "year_level" => "Year Level",
                "code" => "Course Code"
            ];

            if(empty($_POST["code"])){
                $_REQUEST["code"] = create_course_code($_POST["program_id"], $_POST["year_level"], $_POST["course_semester"]);

                if($_REQUEST["code"] === false){
                    $errors["system_message"] = "Course Code could not be generated";
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

            $rules = [
                "course_id" => "required|integer|exists:courses,id",
                "name" => "required|string|unique:courses,name,id != $course_id",
                "course_semester" => "required|integer|positive|max:2|min:1",
                "year_level" => "required|integer|positive",
                "program_id" => "required|integer|exists:programs,id"
            ];

            if(!$errors){
                $data = form_data(exclude: ["name", "code"], key_change: ["course_id" => "id"]);
                $course = courses($course_id);

                if(update($course, $data, "courses", ["id"])){
                    $_SESSION["system_message"] = "Course '{$course['name']}' has been updated";
                }
            }
        }elseif($submit == "create_role"){
            $user_roles = implode(",", get_system_user_roles(true, true));
            $rules = [
                "display_name" => "required|string|unique:user_roles,display_name",
                "role_name" => "required|string|in:$user_roles",
                "permissions" => "nullable|array",
                "name" => "nullable|string|unique:user_roles,name"
            ];

            $exclude = ["role_id"];

            $errors = validate_form($rules);

            if(!$errors){
                $data = form_data(exclude: $exclude);
                if(isset($data["permissions"])){
                    $data["permissions"] = serialize_($data["permissions"]);
                }else{
                    $data["permissions"] = null;
                }

                $status = true;
                $data = "No issues";
            }
        }elseif($submit == "update_role"){
            $user_roles = implode(",", get_system_user_roles(true, true));
            $role_id = $_POST["role_id"];
            $rules = [
                "role_id" => "required|integer|exists:user_roles,id",
                "display_name" => "required|string|unique:user_roles,display_name,id != $role_id",
                "role_name" => "required|string|in:$user_roles",
                "name" => "nullable|string|unique:user_roles,name",
                "permissions" => "nullable|array"
            ];

            $errors = validate_form($rules);

            if(!$errors){
                $data = form_data(key_change: ["role_id" => "id"]);
                if(isset($data["permissions"])){
                    $data["permissions"] = serialize_($data["permissions"]);
                }else{
                    $data["permissions"] = null;
                }

                $status = true;
                $data = "No issues";
            }
        }elseif ($submit == "create_evaluation_form" || $submit == "update_evaluation_form") {
            $is_update = ($submit == "update_evaluation_form");
            $form_id = $_POST['form_id'] ?? null;
            $code = $_POST["unique_code"];
            $admin_id = user()["id"];
        
            $rules = [
                "title" => "required|string|max:255",
                "academic_year" => "required|string|max:9",
                "unique_code" => "required|string|max:50|unique:evaluation_forms,unique_code".$is_update ? ", id != $form_id" : "", // Unique code check (excluding current ID on update)
                "control_type" => "required|in:auto,manual",
                "start_time" => "required|date",
                "end_time" => "required|date|after:start_time",
            ];

            if($is_update){
                $rules["form_id"] = "required|integer|exists:evaluation_forms,id";
            }
        
            $alias = [
                "title" => "Evaluation Title",
                "academic_year" => "Academic Year",
                "unique_code" => "Unique Code",
                "start_time" => "Start Time",
                "end_time" => "End Time",
            ];
            
            // Assuming your validation function handles the `after` rule
            $errors = validate_form($rules, alias:$alias); 
        
            if (!$errors) {
                $data = form_data(exclude: ["form_id"]);
        
                if ($is_update) {
                    // Only update 'last_edited_by' on update
                    $data['last_edited_by'] = $admin_id;
                    $data["id"] = $form_id;
                    
                    // Prevent changing academic year after creation
                    $original_data = fetchData("*", "evaluation_forms", "id = $form_id");
                    $original_year = $original_data['academic_year'] ?? '';
                    if ($data['academic_year'] !== $original_year) {
                         $errors["system_message"] = "The Academic Year cannot be modified after the form has been created.";
                    } else {
                         if (update($original_data, $data, "evaluation_forms", ["id"])) {
                            $new_form_id = $form_id; // Keep the ID for redirection
                            $status = true;
                            $data['message'] = "Evaluation Form updated successfully.";
                         } else {
                            $errors["system_message"] = "Failed to update form details.";
                         }
                    }
                } else {
                    // Set auditing fields on creation
                    $data['created_by'] = $admin_id;
                    
                    // Assuming data_insert returns the new ID on success
                    if ($new_form_id = data_insert("evaluation_forms", $data)) {
                        $status = true;
                        $data['message'] = "Evaluation Form created successfully.";
                    } else {
                        $errors["system_message"] = "Failed to create new form.";
                    }
                }
            }
            
            // If successful, redirect to the question management page
            if (isset($data['status']) && $data['status']) {
                // This is the next page in the flow
                $data['redirect'] = "/admin/evaluation/manage-questions.php?form_id={$new_form_id}";
            }
            // Final AJAX response handling (assumed)
        }elseif ($submit == "create_evaluation_question" || $submit == "update_evaluation_question") {
            $is_update = ($submit == "update_evaluation_question");
            $question_id = $_POST['question_id'] ?? null;
            $form_id = $_POST['form_id'] ?? null;
            $admin_id = user()["id"];
            $rating_types = implode(",", get_question_types(QUESTION_TYPES::EVALUATION, true));

            if($is_update){
                $rules["question_id"] = "required|integer|exists:evaluation_questions,id";

                $exclude = ["form_id"];
                $replace = ["question_id" => "id"];
            }else{
                $exclude = ["question_id"];
            }
            
            // Retrieve data
            $form_data = form_data(exclude: $exclude, key_change: $replace ?? []); // Exclude 'options' for manual handling
            $options_array = $_POST['options'] ?? [];
        
            $rules = [
                "form_id" => "required|integer|exists:evaluation_forms,id",
                "question_text" => "required|string",
                "rating_type" => "required|in:$rating_types",
                "question_order" => "required|integer|min:1",
                "is_required" => "boolean",
            ];
        
            $errors = validate_form($rules); 
        
            // Custom Validation for Select types
            if (in_array($form_data['rating_type'], ['select_single', 'select_multiple'])) {
                // Filter out empty options
                $valid_options = array_filter($options_array, 'trim'); 
                
                if (count($valid_options) < 2) {
                    $errors['options'] = "Questions of type 'Single Choice' or 'Multiple Choice' require at least two options.";
                }
                
                // Prepare options_json for insertion/update
                $form_data['options_json'] = json_encode(array_values($valid_options)); // Re-index array for clean JSON
            }
            
            // Set default for required field if not present (unchecked checkbox)
            $form_data['is_required'] = $form_data['is_required'] ?? 0;
        
            if (!$errors) {
                if ($is_update && $question_id) {
                    // Update logic
                    $form_data['last_edited_by'] = $admin_id;
                    
                    $question = fetchData("*", "evaluation_questions", "id = $question_id");
                    
                    if (update($question, $form_data, "evaluation_questions", ["id"])) {
                        $status = true;
                        $data['message'] = "Question updated successfully.";
                    } else {
                        $errors["system_message"] = "Failed to update question.";
                    }
                } else {
                    // Create logic
                    $form_data['created_by'] = $admin_id;
                    
                    if ($new_q_id = data_insert("evaluation_questions", $form_data)) {
                        $status = true;
                        $data['message'] = "Question added successfully.";
                        $data['new_id'] = $new_q_id;
                    } else {
                        $errors["system_message"] = "Failed to create new question.";
                    }
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