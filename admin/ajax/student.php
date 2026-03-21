<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $request_from = $_SERVER["HTTP_REFERER"];
    $next_request = null;
    $_SESSION["old_input"] = $_REQUEST;

    // mapping variable will be used by buildWhereClause function
    $mapping = [
        "level" => "current_year",
        "program" => "program_id",
        "department" => "department_id",
        "faculty" => "faculty_id"
    ];

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "fetch_students"){
            $filters = form_data();
            $offset = 50 * ($filters["page"] - 1);

            $tables = [
                ["join" => "students departments", "on" => "department_id id", "alias" => "s d"],
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
            ];
            $columns = [
                "s.user_id AS user_id",
                "index_number", "CONCAT(COALESCE(lastname, ''), ' ', COALESCE(firstname, ''), ' ', COALESCE(othernames, '')) AS fullname", "d.name as department_name", "p.name as program_name",
                "gender", "profile_pic", "current_year"
            ];

            $where = buildWhereClause($filters);
            $where[] = "s.approved = TRUE";

            $data["students"] = fetchData($columns, $tables, $where, 50, offset: $offset);

            if($data["students"]){
                $data["total"] = (int) fetchData("COUNT(index_number) AS total", $tables, $where)["total"];
            }else{
                $data["students"] = []; $data["total"] = 0;
            }

            $status = true;
        }elseif($submit == "download_students"){
            require_once $rootPath."/includes/spreadsheet.php";

            try {            
                $filters = form_data();
            
                $tables = [
                    ["join" => "students departments", "on" => "department_id id", "alias" => "s d"],
                    ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
                    ["join" => "students halls", "on" => "hall_id id", "alias" => "s h"],
                    ["join" => "students parent_guardians", "on" => "id student_id", "alias" => "s g"],
                ];
            
                $columns = [
                    "index_number", "CONCAT(COALESCE(lastname, ''), ' ', COALESCE(firstname, ''), ' ', COALESCE(othernames, '')) AS fullname", "d.name as department_name",
                    "p.name as program_name", "gender", "current_year", "date_of_birth", "nationality", "religion",
                    "contact_address", "s.phone_number", "ghana_card", "h.name AS hall_name", "g.name as guardian_name",
                    "g.relationship as guardian_relation", "g.address as guardian_address", "g.phone_number as guardian_phone",
                    "g.email as guardian_email"
                ];
            
                $where = buildWhereClause($filters);
                $where[] = "s.approved = TRUE";
            
                $students = fetchData($columns, $tables, $where, 0);
            
                if (empty($students)) {
                    throw new Exception("No students found for the selected filters.");
                }
            
                // Set up spreadsheet
                $spreadsheet = setup_spreadsheet($students);
            
                // Generate unique filename and folder
                $filename = "students_" . date("Ymd_His");
                $directory = "tmp";
            
                // Save it temporarily (so frontend can download)
                create_spreadsheet($spreadsheet, $filename, $directory, true);
            
                $file_url = "/$directory/$filename.xlsx";
            
                $status = true;
                $data = [
                    "file_url" => url($file_url),
                    "filename" => "$filename.xlsx"
                ];

                // create a deletion job
                add_job("delete_tmp", create_payload("delete_tmp_file", [relative_path($file_url)]), 120);
            } catch (Exception $e) {
                $errors["system_error"] = $e->getMessage();
            }
        }elseif($submit == "fetch_unapproved_students") {
            $filters = form_data();
            $offset = 50 * ($filters["page"] - 1);
        
            // Define all table joins
            $tables = [
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
                ["join" => "students parent_guardians", "on" => "id student_id", "alias" => "s g"]
            ];
        
            // Columns required by frontend
            $columns = [
                "s.user_id", "s.index_number", "s.profile_pic",
                "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) AS fullname",
                "s.gender", "p.name AS program_name", "p.department_id", "s.created_at", "g.id AS guardian"
            ];
        
            // Filters / Where clause
            $where = buildWhereClause($filters);
            $where[] = "s.approved = FALSE"; // Unapproved students only
        
            // Fetch data
            $data["students"] = fetchData($columns, $tables, $where, 50, offset: $offset, join_type: "LEFT");

            if ($data["students"]) {
                // Loop through each student and determine guardian status
                foreach ($data["students"] as &$student) {
                    $student["guardian_provided"] = !empty($student["guardian"]) ? "Provided" : "Not Provided";
                }

                $data["total"] = (int) fetchData("COUNT(s.id) AS total", $tables, $where, join_type: "LEFT")["total"];
            }  else {
                $data["students"] = [];
                $data["total"] = 0;
            }
        
            $status = true;
        }elseif($submit == "fetch_promotions"){
            $filters = form_data();
            $tables = [
                ["join" => "promotions students", "on" => "student_id id", "alias" => "p s"]
            ];
            $columns = ["p.id", "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) as student_name", "p.from_level", "p.to_level", "p.status"];
            $where = buildWhereClause($filters);
            $data["promotions"] = fetchData($columns, $tables, $where, 50);
            $status = true;
        }elseif($submit == "promote_student"){
            $input = form_data();
            $promo_data = [
                'student_id' => $input['student_id'],
                'from_level' => $input['from_level'],
                'to_level' => $input['to_level'],
                'academic_session_id' => $input['academic_session_id'],
                'status' => 'approved'
            ];
            if(data_insert('promotions', $promo_data)){
                update(['id' => $input['student_id']], ['current_year' => $input['to_level']], 'students', ['id']);
                $status = true;
                $data["message"] = "Student promoted";
            }
        }elseif($submit == "fetch_graduations"){
            $filters = form_data();
            $tables = [
                ["join" => "graduations students", "on" => "student_id id", "alias" => "g s"]
            ];
            $columns = ["g.id", "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) as student_name", "g.graduation_year", "g.class_of_honors"];
            $where = buildWhereClause($filters);
            $data["graduations"] = fetchData($columns, $tables, $where, 50);
            $status = true;
        }elseif($submit == "graduate_student"){
            $input = form_data();
            $grad_data = [
                'student_id' => $input['student_id'],
                'graduation_year' => $input['graduation_year'],
                'class_of_honors' => $input['class_of_honors'],
                'status' => 'approved'
            ];
            if(data_insert('graduations', $grad_data)){
                 $status = true;
                 $data["message"] = "Student graduated";
            }
        }elseif($submit == "fetch_medical"){
            $filters = form_data();
            $tables = [
                ["join" => "medical_records students", "on" => "student_id id", "alias" => "m s"]
            ];
            $columns = ["m.id", "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) as student_name", "m.condition_name", "m.date_reported"];
            $where = buildWhereClause($filters);
            $data["medical"] = fetchData($columns, $tables, $where, 50);
            $status = true;
        }elseif($submit == "add_medical"){
            $input = form_data();
            $med_data = [
                'student_id' => $input['student_id'],
                'condition_name' => $input['condition_name'],
                'description' => $input['description'],
                'date_reported' => $input['date_reported'],
                'status' => 'Active'
            ];
            if(data_insert('medical_records', $med_data)){
                $status = true;
                $data["message"] = "Medical record added";
            }
        }elseif($submit == "fetch_discipline"){
            $filters = form_data();
             $tables = [
                ["join" => "discipline_records students", "on" => "student_id id", "alias" => "d s"]
            ];
            $columns = ["d.id", "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) as student_name", "d.offense", "d.punishment", "d.date_committed"];
            $where = buildWhereClause($filters);
            $data["discipline"] = fetchData($columns, $tables, $where, 50);
            $status = true;
        }elseif($submit == "add_discipline"){
            $input = form_data();
            $dis_data = [
                'student_id' => $input['student_id'],
                'offense' => $input['offense'],
                'punishment' => $input['punishment'],
                'date_committed' => $input['date_committed'],
                'status' => 'active'
            ];
            if(data_insert('discipline_records', $dis_data)){
                $status = true;
                $data["message"] = "Discipline record added";
            }
        }
        
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