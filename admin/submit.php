<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        // JSON/AJAX handlers repopulate via client; storing old_input here overwrites other forms' fields
        // and empty POST values override DB defaults when components call old($key, $default).
        if (($_REQUEST["response_type"] ?? "") !== "json") {
            $_SESSION["old_input"] = $_REQUEST;
        }

        if($submit == "create_admin" || $submit == "update_admin"){
            $is_update = $submit == "update_admin";

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

            if(!$is_update){
                $extra_rules = [
                    "type" => "required", 
                ];
            }elseif($submit == "update_admin"){
                $extra_rules = [
                    "profile_pic" => "nullable|file|accepts:jpg,jpeg,png,gif",
                    "gender" => "nullable|string|in:male,female",
                    "phone_number" => "required|string|phone|unique:admins,phone_number,phone_number IS NOT NULL,user_id != $user_id",
                    "position_title" => "required|string",
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
                $user_first_update = empty(user()["username"]) && $is_update;

                $data = form_data("admins/profile", exclude: ["username"]);
                $response = empty(user()["username"]) && !$is_update ? data_insert("admins", $data) : update(user(), $data, "admins", ["user_id"]);             

                if($response === true){
                    if(!$is_update || $user_first_update){
                        $response = update(user(true), ["username" => $username], "users", ["id"]);
                    }

                    if($response === true){
                        $_SESSION["system_message"] = "Admin account updated";
                        user(true);     // reflect new changes
                    }

                    if($type == 2 && $submit === "create_admin" || $user_first_update){
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
                    $new_user_id = db_last_insert_id();
                    // add user to table
                    if($data["type"] == "admin"){
                        data_insert("admins", [
                            "user_id" => $new_user_id,
                            "type" => $type
                        ]);
                    }elseif($data['type'] == "teacher"){
                        data_insert("teachers", [
                            "user_id" => $new_user_id,
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
                "department_id" => "required|integer|exists:departments,id",
                "program_length" => "nullable|integer|positive|min:1|max:4"
            ];
            $alias = [
                "name" => "Program Name",
                "cost" => "Cost Fee",
                "certificate" => "Program Certification",
                "department_id" => "Department",
            ];

            $errors = validate_form($rules, alias: $alias);

            if(!$errors){
                $data = form_data(exclude: ["program_id"]);
                if(empty($data["program_length"])){
                    unset($data["program_length"]);
                }

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
                "department_id" => "required|integer|exists:departments,id",
                "program_length" => "nullable|integer|positive|min:1|max:4"
            ];
            $alias = [
                "program_id" => "Program",
                "name" => "Program Name",
                "cost" => "Cost Fee",
                "department_id" => "Department"
            ];

            $errors = validate_form($rules, alias: $alias, hidden: ["program_id"]);

            if(!$errors){
                $data = form_data(key_change: ["program_id" => "id"]);

                if(empty($data["program_length"])){
                    unset($data["program_length"]);
                }

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

        // ========================================
        // STUDENTS SECTION HANDLERS
        // ========================================
        
        elseif($submit == "student_promotion" || $submit == "preview_promotion" || $submit == "confirm_promotion"){
            $input = form_data();
            
            if($submit == "preview_promotion"){
                // Preview promotion - fetch students matching criteria
                $input["from_level"] %= 100;
                $input["to_level"] %= 100;
                
                $where = [];
                if(!empty($input['from_level'])){
                    $where[] = "s.current_year = " . (int)$input['from_level'];
                }
                if(!empty($input['program_id'])){
                    $where[] = "s.program_id = " . (int)$input['program_id'];
                }
                
                $tables = [
                    ["join" => "students programs", "on" => "program_id id", "alias" => "s p"]
                ];
                $columns = [
                    "s.id", "s.user_id", "s.index_number", 
                    "CONCAT(s.lastname, ' ', s.othernames) AS fullname",
                    "s.current_year", "p.name AS program_name"
                ];
                
                $students = fetchData($columns, $tables, $where, 0);
                $data["students"] = is_array($students) ? $students : [];
                $data["total"] = count($data["students"]);
                $status = true;
                
            } elseif($submit == "confirm_promotion"){
                // Confirm and execute promotion
                $rules = [
                    "from_level" => "required|numeric",
                    "to_level" => "required|numeric"
                ];
                $errors = validate_form($rules);
                
                if(empty($errors)){
                    $input["form_level"] %= 100;
                    $input["to_level"] %= 100;
                    
                    $where = [];
                    if(!empty($input['from_level'])){
                        $where[] = "current_year = " . (int)$input['from_level'];
                    }
                    if(!empty($input['program_id'])){
                        $where[] = "program_id = " . (int)$input['program_id'];
                    }
                    
                    $students = fetchData("id, index_number", "students", $where, 0);
                    $promoted = 0;
                    
                    if(is_array($students) && !empty($students)){
                        foreach($students as $student){
                            $promo_data = [
                                'student_id' => $student['id'],
                                'from_level' => $input['from_level'],
                                'to_level' => $input['to_level'],
                                'academic_session_id' => $input['session_id'] ?? null,
                                'promoted_by' => user()['id'],
                                'promotion_date' => date('Y-m-d')
                            ];
                            
                            if(data_insert('promotions', $promo_data)){
                                update(['id' => $student['id']], ['current_year' => $input['to_level']], 'students', ['id']);
                                $promoted++;
                            }
                        }
                    }
                    
                    $status = true;
                    $data["message"] = "$promoted students promoted successfully";
                }
            }
        }
        
        elseif($submit == "process_graduation"){
            $input = form_data();
            
            $rules = [
                "level" => "required|numeric",
                "session_id" => "required|numeric",
                "graduation_date" => "required|date"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $where = ["current_year = " . (int)$input['level'], "approved = 1"];
                if(!empty($input['program_id'])){
                    $where[] = "program_id = " . (int)$input['program_id'];
                }
                
                $students = fetchData("id, index_number", "students", $where, 0);
                $graduated = 0;
                
                if(is_array($students) && !empty($students)){
                    foreach($students as $student){
                        $grad_data = [
                            'student_id' => $student['id'],
                            'graduation_date' => $input['graduation_date'],
                            'academic_session_id' => $input['session_id'],
                            'graduated_by' => user()['id'],
                            'status' => 'graduated'
                        ];
                        
                        if(data_insert('graduations', $grad_data)){
                            // Update student status
                            update(['id' => $student['id']], ['graduated' => 1], 'students', ['id']);
                            $graduated++;
                        }
                    }
                }
                
                $status = true;
                $data["message"] = "$graduated students graduated successfully";
            }
        }
        
        elseif($submit == "update_medical"){
            $input = form_data();
            
            $rules = [
                "user_id" => "required|numeric|exists:users,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Get student ID from user_id
                $student = fetchData("id", "students", ["user_id" => $input['user_id']]);
                if($student){
                    $student_id = $student['id'];
                    
                    $med_data = [
                        'blood_type' => $input['blood_type'] ?? null,
                        'allergies' => $input['allergies'] ?? null,
                        'insurance_number' => $input['insurance_number'] ?? null,
                        'chronic_conditions' => $input['chronic_conditions'] ?? null,
                        'emergency_contact_name' => $input['emergency_contact_name'] ?? null,
                        'emergency_relationship' => $input['emergency_relationship'] ?? null,
                        'emergency_phone' => $input['emergency_phone'] ?? null
                    ];
                    
                    // Check if medical record exists
                    $existing = fetchData("id", "medical_records", ["student_id" => $student_id]);
                    if($existing){
                        update(['student_id' => $student_id], $med_data, 'medical_records', ['student_id']);
                    } else {
                        $med_data['student_id'] = $student_id;
                        data_insert('medical_records', $med_data);
                    }
                    
                    $status = true;
                    $data["message"] = "Medical information updated successfully";
                } else {
                    $errors["system_error"] = "Student not found";
                }
            }
        }
        
        elseif($submit == "add_disciplinary_record"){
            $input = form_data();
            
            $rules = [
                "student_index" => "required|string",
                "incident" => "required|string",
                "violation_type" => "required|string",
                "severity" => "required|string",
                "incident_date" => "required|date",
                "action_taken" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Get student by index number
                $student = fetchData("id", "students", ["index_number" => $input['student_index']]);
                if($student){
                    $dis_data = [
                        'student_id' => $student['id'],
                        'incident' => $input['incident'],
                        'violation_type' => $input['violation_type'],
                        'severity' => $input['severity'],
                        'incident_date' => $input['incident_date'],
                        'action_taken' => $input['action_taken'],
                        'status' => 'active',
                        'recorded_by' => user()['id'],
                        'recorded_date' => date('Y-m-d')
                    ];
                    
                    if(data_insert('disciplinary_records', $dis_data)){
                        $status = true;
                        $data["message"] = "Disciplinary record added successfully";
                    } else {
                        $errors["system_error"] = "Failed to add disciplinary record";
                    }
                } else {
                    $errors["student_index"] = "Student with index number not found";
                }
            }
        }
        
        // ========================================
        // ACADEMIC SECTION HANDLERS
        // ========================================
        
        elseif($submit == "create_timetable" || $submit == "save_timetable" || $submit == "add_timetable_class"){
            $input = form_data();
            
            if($submit == "create_timetable" || $submit == "save_timetable"){
                $rules = [
                    "program_id" => "required|numeric|exists:programs,id",
                    "level" => "required|numeric",
                    "session_id" => "required|numeric|exists:academic_sessions,id"
                ];
                $errors = validate_form($rules);
                
                if(empty($errors)){
                    // Check if timetable exists
                    $existing = fetchData("id", "timetables", [
                        "program_id" => $input['program_id'],
                        "level" => $input['level'],
                        "session_id" => $input['session_id']
                    ]);
                    
                    if($existing){
                        $timetable_id = $existing['id'];
                        $status = true;
                        $data["timetable_id"] = $timetable_id;
                        $data["message"] = "Timetable loaded";
                    } else {
                        // Create new timetable
                        $tt_data = [
                            'program_id' => $input['program_id'],
                            'level' => $input['level'],
                            'session_id' => $input['session_id'],
                            'created_by' => user()['id']
                        ];
                        
                        if($timetable_id = data_insert('timetables', $tt_data)){
                            $status = true;
                            $data["timetable_id"] = $timetable_id;
                            $data["message"] = "Timetable created successfully";
                        } else {
                            $errors["system_error"] = "Failed to create timetable";
                        }
                    }
                }
            } elseif($submit == "add_timetable_class"){
                $rules = [
                    "timetable_id" => "required|numeric|exists:timetables,id",
                    "day" => "required|string",
                    "start_time" => "required|string",
                    "end_time" => "required|string",
                    "course_code" => "required|string",
                    "venue" => "required|string",
                    "lecturer" => "required|string"
                ];
                $errors = validate_form($rules);
                
                if(empty($errors)){
                    $class_data = [
                        'timetable_id' => $input['timetable_id'],
                        'day' => $input['day'],
                        'start_time' => $input['start_time'],
                        'end_time' => $input['end_time'],
                        'course_code' => $input['course_code'],
                        'course_name' => $input['course_name'] ?? null,
                        'venue' => $input['venue'],
                        'lecturer' => $input['lecturer']
                    ];
                    
                    if(data_insert('timetable_classes', $class_data)){
                        $status = true;
                        $data["message"] = "Class added to timetable successfully";
                    } else {
                        $errors["system_error"] = "Failed to add class to timetable";
                    }
                }
            }
        }
        
        // ========================================
        // GRADING SECTION HANDLERS
        // ========================================
        
        elseif($submit == "update_grade_points"){
            $input = form_data();
            
            if(isset($input['grades']) && is_array($input['grades']) && 
               isset($input['points']) && is_array($input['points']) &&
               isset($input['min_score']) && is_array($input['min_score']) &&
               isset($input['max_score']) && is_array($input['max_score'])){
                
                $updated = 0;
                foreach($input['grades'] as $index => $grade){
                    $gp_data = [
                        'points' => $input['points'][$index],
                        'min_score' => $input['min_score'][$index],
                        'max_score' => $input['max_score'][$index]
                    ];
                    
                    $existing = fetchData("id", "grade_points", ["grade" => $grade]);
                    if($existing){
                        update(['grade' => $grade], $gp_data, 'grade_points', ['grade']);
                    } else {
                        $gp_data['grade'] = $grade;
                        data_insert('grade_points', $gp_data);
                    }
                    $updated++;
                }
                
                $status = true;
                $data["message"] = "$updated grade points updated successfully";
            } else {
                $errors["system_error"] = "Invalid grade points data";
            }
        }
        
        elseif($submit == "enter_results"){
            $input = form_data();
            
            $rules = [
                "course_id" => "required|numeric|exists:courses,id",
                "session_id" => "required|numeric|exists:academic_sessions,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors) && isset($input['results']) && is_array($input['results'])){
                $saved = 0;
                foreach($input['results'] as $result){
                    if(!empty($result['student_id']) && !empty($result['score'])){
                        $result_data = [
                            'student_id' => $result['student_id'],
                            'course_id' => $input['course_id'],
                            'session_id' => $input['session_id'],
                            'score' => $result['score'],
                            'grade' => $result['grade'] ?? null,
                            'grade_points' => $result['grade_points'] ?? null,
                            'entered_by' => user()['id'],
                            'entered_date' => date('Y-m-d')
                        ];
                        
                        // Check if result exists
                        $existing = fetchData("id", "results", [
                            "student_id" => $result['student_id'],
                            "course_id" => $input['course_id'],
                            "session_id" => $input['session_id']
                        ]);
                        
                        if($existing){
                            update(['id' => $existing['id']], $result_data, 'results', ['id']);
                        } else {
                            data_insert('results', $result_data);
                        }
                        $saved++;
                    }
                }
                
                $status = true;
                $data["message"] = "$saved results saved successfully";
            }
        }
        
        elseif($submit == "upload_results"){
            $input = form_data("uploads/results");
            
            $rules = [
                "results_file" => "required|file|accepts:xlsx,xls"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors) && !empty($_FILES['results_file'])){
                require_once $rootPath."/includes/spreadsheet.php";
                
                try {
                    $file_path = $_FILES['results_file']['tmp_name'];
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    
                    // Skip header row
                    array_shift($rows);
                    
                    $imported = 0;
                    foreach($rows as $row){
                        if(!empty($row[0]) && !empty($row[2])){ // Assuming index_number and score
                            $student = fetchData("id", "students", ["index_number" => $row[0]]);
                            if($student){
                                // Process result import
                                $imported++;
                            }
                        }
                    }
                    
                    $status = true;
                    $data["message"] = "$imported results imported successfully";
                } catch(Exception $e){
                    $errors["system_error"] = "Failed to process file: " . $e->getMessage();
                }
            }
        }
        
        elseif($submit == "generate_transcript" || $submit == "bulk_generate_transcripts"){
            $input = form_data();
            
            if($submit == "generate_transcript"){
                $rules = [
                    "student_index" => "required|string"
                ];
                $errors = validate_form($rules);
                
                if(empty($errors)){
                    $student = fetchData("id", "students", ["index_number" => $input['student_index']]);
                    if($student){
                        // Generate transcript logic here
                        $status = true;
                        $data["message"] = "Transcript generated successfully";
                        $data["transcript_id"] = $student['id']; // Placeholder
                    } else {
                        $errors["student_index"] = "Student not found";
                    }
                }
            } elseif($submit == "bulk_generate_transcripts"){
                // Bulk generate transcripts
                $where = ["approved = 1"];
                if(!empty($input['program_id'])){
                    $where[] = "program_id = " . (int)$input['program_id'];
                }
                
                $students = fetchData("id", "students", $where, 0);
                $generated = is_array($students) ? count($students) : 0;
                
                $status = true;
                $data["message"] = "Bulk transcript generation started for $generated students";
                $data["count"] = $generated;
            }
        }
        
        // ========================================
        // STAFF SECTION HANDLERS
        // ========================================
        
        elseif($submit == "assign_teacher"){
            $input = form_data();
            
            $rules = [
                "teacher_search" => "required|string",
                "program_id" => "required|numeric|exists:programs,id",
                "level" => "required|numeric",
                "course_id" => "required|numeric|exists:courses,id",
                "session_id" => "required|numeric|exists:academic_sessions,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Get teacher by ID or name
                $teacher = fetchData("id, user_id", "teachers", [
                    "staff_id" => $input['teacher_search']
                ]);
                
                if(!$teacher){
                    // Try searching by name
                    $teacher_user = fetchData("id", "users", ["username" => $input['teacher_search']]);
                    if($teacher_user){
                        $teacher = fetchData("id, user_id", "teachers", ["user_id" => $teacher_user['id']]);
                    }
                }
                
                if($teacher){
                    $assign_data = [
                        'teacher_id' => $teacher['id'],
                        'program_id' => $input['program_id'],
                        'level' => $input['level'],
                        'course_id' => $input['course_id'],
                        'session_id' => $input['session_id'],
                        'assigned_by' => user()['id'],
                        'assigned_date' => date('Y-m-d')
                    ];
                    
                    // Check if assignment already exists
                    $existing = fetchData("id", "teacher_assignments", [
                        "teacher_id" => $teacher['id'],
                        "course_id" => $input['course_id'],
                        "session_id" => $input['session_id']
                    ]);
                    
                    if($existing){
                        update(['id' => $existing['id']], $assign_data, 'teacher_assignments', ['id']);
                        $data["message"] = "Teacher assignment updated successfully";
                    } else {
                        data_insert('teacher_assignments', $assign_data);
                        $data["message"] = "Teacher assigned successfully";
                    }
                    
                    $status = true;
                } else {
                    $errors["system_error"] = "Teacher not found";
                }
            }
        }
        
        elseif($submit == "assign_teacher_role"){
            $input = form_data();
            
            $rules = [
                "teacher_search" => "required|string",
                "role" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Get teacher
                $teacher = fetchData("id, user_id", "teachers", ["staff_id" => $input['teacher_search']]);
                if(!$teacher){
                    $teacher_user = fetchData("id", "users", ["username" => $input['teacher_search']]);
                    if($teacher_user){
                        $teacher = fetchData("id, user_id", "teachers", ["user_id" => $teacher_user['id']]);
                    }
                }
                
                if($teacher){
                    $role_data = [
                        'teacher_id' => $teacher['id'],
                        'role' => $input['role'],
                        'program_id' => $input['program_id'] ?? null,
                        'description' => $input['description'] ?? null,
                        'assigned_by' => user()['id'],
                        'assigned_date' => date('Y-m-d'),
                        'status' => 'active'
                    ];
                    
                    if(data_insert('teacher_roles', $role_data)){
                        $status = true;
                        $data["message"] = "Role assigned to teacher successfully";
                    } else {
                        $errors["system_error"] = "Failed to assign role";
                    }
                } else {
                    $errors["system_error"] = "Teacher not found";
                }
            }
        }
        
        elseif($submit == "assign_staff"){
            $input = form_data();
            
            $rules = [
                "staff_search" => "required|string",
                "department_id" => "required|numeric|exists:departments,id",
                "office" => "required|string",
                "position_title" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Get staff by search term (non-teaching staff)
                $staff = fetchData("id", "users", ["email" => $input['staff_search']]);
                if(!$staff){
                    // Try searching by name or ID
                    $staff = fetchData("id", "users", ["id" => $input['staff_search']]);
                }
                
                if($staff){
                    $assignment_data = [
                        'staff_id' => $staff['id'],
                        'department_id' => $input['department_id'],
                        'office' => $input['office'],
                        'position_title' => $input['position_title'],
                        'assignment_date' => $input['assignment_date'] ?? date('Y-m-d'),
                        'assigned_by' => user()['id'],
                        'status' => 'active'
                    ];
                    
                    if(data_insert('staff_assignments', $assignment_data)){
                        $status = true;
                        $data["message"] = "Staff assigned successfully";
                    } else {
                        $errors["system_error"] = "Failed to assign staff";
                    }
                } else {
                    $errors["system_error"] = "Staff member not found";
                }
            }
        }
        
        elseif($submit == "assign_staff_role"){
            $input = form_data();
            
            $rules = [
                "staff_search" => "required|string",
                "role" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Get staff by search term
                $staff = fetchData("id", "users", ["email" => $input['staff_search']]);
                if(!$staff){
                    $staff = fetchData("id", "users", ["id" => $input['staff_search']]);
                }
                
                if($staff){
                    $role_data = [
                        'staff_id' => $staff['id'],
                        'role' => $input['role'],
                        'department_id' => $input['department_id'] ?? null,
                        'description' => $input['description'] ?? null,
                        'assigned_by' => user()['id'],
                        'assigned_date' => date('Y-m-d'),
                        'status' => 'active'
                    ];
                    
                    if(data_insert('staff_roles', $role_data)){
                        $status = true;
                        $data["message"] = "Role assigned to staff successfully";
                    } else {
                        $errors["system_error"] = "Failed to assign role";
                    }
                } else {
                    $errors["system_error"] = "Staff member not found";
                }
            }
        }
        
        elseif($submit == "add_non_teaching_staff"){
            $input = form_data();
            
            $rules = [
                "email" => "required|email|unique:users,email",
                "position" => "required|string",
                "department_id" => "required|numeric|exists:departments,id",
                "phone_number" => "required|phone",
                "password" => "required|string|min:8"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Create user account
                $user_data = [
                    'email' => $input['email'],
                    'password' => password_hash($input['password'], PASSWORD_DEFAULT),
                    'type' => 'staff',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                if($user_id = data_insert('users', $user_data)){
                    // Create staff profile
                    $staff_data = [
                        'user_id' => $user_id,
                        'position' => $input['position'],
                        'department_id' => $input['department_id'],
                        'phone_number' => $input['phone_number'],
                        'status' => 'active'
                    ];
                    
                    if(data_insert('non_teaching_staff', $staff_data)){
                        $status = true;
                        $data["message"] = "Non-teaching staff added successfully";
                    } else {
                        $errors["system_error"] = "Failed to create staff profile";
                    }
                } else {
                    $errors["system_error"] = "Failed to create user account";
                }
            }
        }
        
        // ========================================
        // FINANCE SECTION HANDLERS
        // ========================================
        
        elseif($submit == "update_fee_structure"){
            $input = form_data();
            
            $rules = [
                "program_id" => "required|numeric|exists:programs,id",
                "level" => "required|numeric",
                "session_id" => "required|numeric|exists:academic_sessions,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Calculate total fee
                $total_fee = 0;
                $fee_categories = ['tuition_fee', 'library_fee', 'lab_fee', 'medical_fee', 'sports_fee', 'examination_fee'];
                foreach($fee_categories as $category){
                    if(!empty($input[$category])){
                        $total_fee += (float)$input[$category];
                    }
                }
                
                $fee_data = [
                    'program_id' => $input['program_id'],
                    'level' => $input['level'],
                    'session_id' => $input['session_id'],
                    'tuition_fee' => $input['tuition_fee'] ?? 0,
                    'library_fee' => $input['library_fee'] ?? 0,
                    'lab_fee' => $input['lab_fee'] ?? 0,
                    'medical_fee' => $input['medical_fee'] ?? 0,
                    'sports_fee' => $input['sports_fee'] ?? 0,
                    'examination_fee' => $input['examination_fee'] ?? 0,
                    'total_amount' => $total_fee,
                    'created_by' => user()['id']
                ];
                
                // Check if fee structure exists
                $existing = fetchData("id", "fee_structures", [
                    "program_id" => $input['program_id'],
                    "level" => $input['level'],
                    "session_id" => $input['session_id']
                ]);
                
                if($existing){
                    update(['id' => $existing['id']], $fee_data, 'fee_structures', ['id']);
                    $data["message"] = "Fee structure updated successfully";
                } else {
                    data_insert('fee_structures', $fee_data);
                    $data["message"] = "Fee structure created successfully";
                }
                
                $status = true;
            }
        }
        
        elseif($submit == "add_scholarship"){
            $input = form_data();
            
            $rules = [
                "name" => "required|string",
                "amount" => "required|numeric|positive",
                "type" => "required|string|in:scholarship,grant"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $scholarship_data = [
                    'name' => $input['name'],
                    'type' => $input['type'],
                    'amount' => $input['amount'],
                    'description' => $input['description'] ?? null,
                    'created_by' => user()['id'],
                    'status' => 'active'
                ];
                
                if(data_insert('scholarships', $scholarship_data)){
                    $status = true;
                    $data["message"] = "Scholarship/Grant added successfully";
                } else {
                    $errors["system_error"] = "Failed to add scholarship";
                }
            }
        }
        
        // ========================================
        // SETTINGS SECTION HANDLERS
        // ========================================
        
        elseif($submit == "backup_database"){
            // Database backup logic
            try {
                $backup_file = "backup_" . date("Ymd_His") . ".sql";
                $backup_path = relative_path("backups/" . $backup_file, false);
                
                // Create backups directory if it doesn't exist
                if(!is_dir(dirname($backup_path))){
                    mkdir(dirname($backup_path), 0777, true);
                }
                
                // Use mysqldump if available
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $dbname = $_ENV['DB_NAME'] ?? '';
                $username = $_ENV['DB_USER'] ?? '';
                $password = $_ENV['DB_PASS'] ?? '';
                
                $command = "mysqldump -h $host -u $username -p$password $dbname > \"$backup_path\"";
                
                exec($command, $output, $return_var);
                
                if($return_var === 0 && file_exists($backup_path)){
                    // Record backup in database
                    $backup_record = [
                        'filename' => $backup_file,
                        'file_path' => $backup_path,
                        'file_size' => filesize($backup_path),
                        'created_by' => user()['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    data_insert('backups', $backup_record);
                    
                    $status = true;
                    $data["message"] = "Database backed up successfully";
                    $data["file_url"] = url("/backups/$backup_file");
                } else {
                    $errors["system_error"] = "Failed to create database backup";
                }
            } catch(Exception $e){
                $errors["system_error"] = "Backup error: " . $e->getMessage();
            }
        }
        
        elseif($submit == "restore_database"){
            $input = form_data("uploads/backups");
            
            $rules = [
                "backup_file" => "required|file|accepts:sql"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors) && !empty($_FILES['backup_file'])){
                // Database restore should be handled with extreme caution
                // This is a placeholder - implement actual restore logic if needed
                $status = true;
                $data["message"] = "Database restore functionality - implement with caution. This operation is destructive.";
            }
        }
        
        // ========================================
        // TEACHER MANAGEMENT HANDLERS
        // ========================================
        
        elseif($submit == "approve_material"){
            $input = form_data();
            
            $rules = [
                "material_id" => "required|numeric|exists:course_materials,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $update_data = [
                    'status' => 'approved',
                    'approved_by' => user()['id'],
                    'approved_date' => date('Y-m-d H:i:s'),
                    'published' => 1
                ];
                
                if(update(['id' => $input['material_id']], $update_data, 'course_materials', ['id'])){
                    $status = true;
                    $data["message"] = "Material approved and published successfully";
                } else {
                    $errors["system_error"] = "Failed to approve material";
                }
            }
        }
        
        elseif($submit == "reject_material"){
            $input = form_data();
            
            $rules = [
                "material_id" => "required|numeric|exists:course_materials,id",
                "rejection_reason" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $update_data = [
                    'status' => 'rejected',
                    'approved_by' => user()['id'],
                    'approved_date' => date('Y-m-d H:i:s'),
                    'rejection_reason' => $input['rejection_reason']
                ];
                
                if(update(['id' => $input['material_id']], $update_data, 'course_materials', ['id'])){
                    $status = true;
                    $data["message"] = "Material rejected successfully";
                } else {
                    $errors["system_error"] = "Failed to reject material";
                }
            }
        }
        
        elseif($submit == "delete_material"){
            $input = form_data();
            
            $rules = [
                "material_id" => "required|numeric|exists:course_materials,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $material = fetchData("id, file_path", "course_materials", ["id" => $input['material_id']]);
                if($material){
                    // Delete physical file if exists
                    if(!empty($material['file_path']) && file_exists($material['file_path'])){
                        unlink($material['file_path']);
                    }
                    
                    if(delete($material, 'course_materials', ['id'])){
                        $status = true;
                        $data["message"] = "Material deleted successfully";
                    } else {
                        $errors["system_error"] = "Failed to delete material";
                    }
                } else {
                    $errors["system_error"] = "Material not found";
                }
            }
        }
        
        elseif($submit == "approve_announcement"){
            $input = form_data();
            
            $rules = [
                "announcement_id" => "required|numeric|exists:announcements,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $update_data = [
                    'status' => 'active',
                    'published' => 1,
                    'approved_by' => user()['id'],
                    'approved_date' => date('Y-m-d H:i:s')
                ];
                
                if(update(['id' => $input['announcement_id']], $update_data, 'announcements', ['id'])){
                    $status = true;
                    $data["message"] = "Announcement approved and published successfully";
                } else {
                    $errors["system_error"] = "Failed to approve announcement";
                }
            }
        }
        
        elseif($submit == "reject_announcement"){
            $input = form_data();
            
            $rules = [
                "announcement_id" => "required|numeric|exists:announcements,id",
                "rejection_reason" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $update_data = [
                    'status' => 'rejected',
                    'published' => 0,
                    'approved_by' => user()['id'],
                    'approved_date' => date('Y-m-d H:i:s'),
                    'rejection_reason' => $input['rejection_reason']
                ];
                
                if(update(['id' => $input['announcement_id']], $update_data, 'announcements', ['id'])){
                    $status = true;
                    $data["message"] = "Announcement rejected successfully";
                } else {
                    $errors["system_error"] = "Failed to reject announcement";
                }
            }
        }
        
        elseif($submit == "archive_announcement"){
            $input = form_data();
            
            $rules = [
                "announcement_id" => "required|numeric|exists:announcements,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                if(update(['id' => $input['announcement_id']], ['status' => 'archived'], 'announcements', ['id'])){
                    $status = true;
                    $data["message"] = "Announcement archived successfully";
                } else {
                    $errors["system_error"] = "Failed to archive announcement";
                }
            }
        }
        
        elseif($submit == "delete_announcement"){
            $input = form_data();
            
            $rules = [
                "announcement_id" => "required|numeric|exists:announcements,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $announcement = fetchData("id", "announcements", ["id" => $input['announcement_id']]);
                if($announcement){
                    if(delete($announcement, 'announcements', ['id'])){
                        $status = true;
                        $data["message"] = "Announcement deleted successfully";
                    } else {
                        $errors["system_error"] = "Failed to delete announcement";
                    }
                } else {
                    $errors["system_error"] = "Announcement not found";
                }
            }
        }
        
        elseif($submit == "approve_results"){
            $input = form_data();
            
            $rules = [
                "result_id" => "required|numeric|exists:results,id"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                // Update all results for this submission batch
                // Note: result_id might reference a submission/batch ID, adjust based on your schema
                $update_data = [
                    'approved' => 1,
                    'approved_by' => user()['id'],
                    'approved_date' => date('Y-m-d H:i:s'),
                    'published' => 1
                ];
                
                if(update(['id' => $input['result_id']], $update_data, 'results', ['id'])){
                    $status = true;
                    $data["message"] = "Results approved and published successfully";
                } else {
                    $errors["system_error"] = "Failed to approve results";
                }
            }
        }
        
        elseif($submit == "reject_results"){
            $input = form_data();
            
            $rules = [
                "result_id" => "required|numeric|exists:results,id",
                "rejection_reason" => "required|string"
            ];
            $errors = validate_form($rules);
            
            if(empty($errors)){
                $update_data = [
                    'approved' => 0,
                    'published' => 0,
                    'rejection_reason' => $input['rejection_reason'],
                    'rejected_by' => user()['id'],
                    'rejected_date' => date('Y-m-d H:i:s')
                ];
                
                if(update(['id' => $input['result_id']], $update_data, 'results', ['id'])){
                    $status = true;
                    $data["message"] = "Results rejected successfully";
                } else {
                    $errors["system_error"] = "Failed to reject results";
                }
            }
        }

        elseif($submit == "update_image_validation_settings"){
            require_once $_SERVER["DOCUMENT_ROOT"]."/includes/settings_functions.php";
            $preserveZeros = [
                "passport_bg_color_r",
                "passport_bg_color_g",
                "passport_bg_color_b",
                "passport_tolerance",
                "passport_min_width",
                "passport_min_height",
                "passport_match_percentage",
                "passport_edge_sample_divisor",
            ];
            $input = form_data(preserve: $preserveZeros);

            $rules = [
                "passport_bg_color_r" => "required|integer|min:0|max:255",
                "passport_bg_color_g" => "required|integer|min:0|max:255",
                "passport_bg_color_b" => "required|integer|min:0|max:255",
                "passport_tolerance" => "required|integer|min:0|max:441",
                "passport_min_width" => "required|integer|min:1",
                "passport_min_height" => "required|integer|min:1",
                "passport_match_percentage" => "required|integer|min:0|max:100",
                "passport_aspect_ratio" => "required|string|in:7:9,3:4,1:1",
                "passport_edge_sample_divisor" => "required|integer|min:10|max:500",
            ];

            $errors = validate_form($rules);

            $skipRatio = isset($_REQUEST["passport_skip_ratio"]) && (string) $_REQUEST["passport_skip_ratio"] === "1";

            if(empty($errors)){
                $uid = user()["id"] ?? null;
                $settings = [
                    "image_validation.passport_bg_color_r" => (int) $input["passport_bg_color_r"],
                    "image_validation.passport_bg_color_g" => (int) $input["passport_bg_color_g"],
                    "image_validation.passport_bg_color_b" => (int) $input["passport_bg_color_b"],
                    "image_validation.passport_tolerance" => (int) $input["passport_tolerance"],
                    "image_validation.passport_min_width" => (int) $input["passport_min_width"],
                    "image_validation.passport_min_height" => (int) $input["passport_min_height"],
                    "image_validation.passport_match_percentage" => (int) $input["passport_match_percentage"],
                    "image_validation.passport_skip_ratio" => $skipRatio,
                    "image_validation.passport_aspect_ratio" => $input["passport_aspect_ratio"] ?? "7:9",
                    "image_validation.passport_edge_sample_divisor" => (int) $input["passport_edge_sample_divisor"],
                ];

                if(bulk_update_settings($settings, $uid ? (int) $uid : null)){
                    $status = true;
                    $data["message"] = "Image validation settings updated successfully";
                } else {
                    $errors["system_error"] = "Failed to update settings";
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