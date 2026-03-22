<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $request_from = $_SERVER["HTTP_REFERER"];
    $next_request = null;
    if (($_REQUEST["response_type"] ?? "") !== "json") {
        $_SESSION["old_input"] = $_REQUEST;
    }

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
            $columns = ["p.id", "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) as student_name", "p.from_level", "p.to_level", "p.promotion_date"];
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
                'promoted_by' => user()['id'] ?? null,
                'promotion_date' => date('Y-m-d'),
            ];
            if(data_insert('promotions', $promo_data)){
                update(['id' => $input['student_id']], ['current_year' => $input['to_level']], 'students', ['id']);
                $status = true;
                $data["message"] = "Student promoted";
            }
        }elseif($submit == "get_graduation_stats"){
            $totalGrad = fetchData("COUNT(*) AS c", "students", ["graduated = 1"], 1);
            $thisYear = fetchData("COUNT(*) AS c", "graduations", ["YEAR(graduation_date) = " . (int)date('Y')], 1);
            $data["total"] = (int)($totalGrad["c"] ?? 0);
            $data["this_year"] = (int)($thisYear["c"] ?? 0);
            $status = true;
        }elseif($submit == "fetch_graduated_students"){
            $input = form_data();
            $tables = [
                ["join" => "graduations students", "on" => "student_id id", "alias" => "g s"],
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
            ];
            $columns = [
                "g.id",
                "s.index_number",
                "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) AS fullname",
                "p.name AS program_name",
                "g.graduation_date",
                "g.status",
            ];
            $where = ["s.graduated = 1"];
            if (!empty($input["program_id"])) {
                $where[] = "s.program_id = " . (int)$input["program_id"];
            }
            if (!empty($input["from_date"])) {
                $where[] = "g.graduation_date >= '" . addslashes($input["from_date"]) . "'";
            }
            if (!empty($input["to_date"])) {
                $where[] = "g.graduation_date <= '" . addslashes($input["to_date"]) . "'";
            }
            $rows = fetchData($columns, $tables, $where, 200, order_by: "g.graduation_date", asc: false);
            $data["students"] = is_array($rows) ? (isset($rows["id"]) ? [$rows] : $rows) : [];
            $status = true;
        }elseif($submit == "fetch_graduations"){
            $filters = form_data();
            $tables = [
                ["join" => "graduations students", "on" => "student_id id", "alias" => "g s"],
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
            ];
            $columns = [
                "g.id",
                "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) AS student_name",
                "g.graduation_date",
                "g.status",
                "p.name AS program_name",
            ];
            $where = buildWhereClause($filters);
            $data["graduations"] = fetchData($columns, $tables, $where, 50);
            $status = true;
        }elseif($submit == "graduate_student"){
            $input = form_data();
            $grad_data = [
                'student_id' => $input['student_id'],
                'graduation_date' => $input['graduation_date'] ?? date('Y-m-d'),
                'academic_session_id' => $input['session_id'] ?? $input['academic_session_id'] ?? null,
                'graduated_by' => user()['id'] ?? null,
                'status' => 'graduated',
            ];
            if(!empty($grad_data['academic_session_id']) && data_insert('graduations', $grad_data)){
                update(['id' => $input['student_id']], ['graduated' => 1], 'students', ['id']);
                $status = true;
                $data["message"] = "Student graduated";
            }
        }elseif($submit == "search_students"){
            $q = trim((string)($_REQUEST["q"] ?? ""));
            $programId = (int)($_REQUEST["program_id"] ?? 0);
            $limit = min(30, max(1, (int)($_REQUEST["limit"] ?? 20)));
            if (strlen($q) < 1) {
                $data["students"] = [];
                $status = true;
            } else {
                $esc = addslashes($q);
                $tables = [
                    ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
                ];
                $columns = [
                    "s.id",
                    "s.user_id",
                    "s.index_number",
                    "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) AS fullname",
                    "s.current_year",
                    "p.name AS program_name",
                ];
                $where = [
                    "s.approved = 1",
                    "(s.index_number LIKE '%{$esc}%' OR s.lastname LIKE '%{$esc}%' OR s.firstname LIKE '%{$esc}%' OR s.othernames LIKE '%{$esc}%')",
                ];
                if ($programId > 0) {
                    $where[] = "s.program_id = {$programId}";
                }
                $rows = fetchData($columns, $tables, $where, $limit);
                $data["students"] = is_array($rows) ? (isset($rows["id"]) ? [$rows] : $rows) : [];
                $status = true;
            }
        }elseif($submit == "search_medical_students"){
            $q = trim((string)($_REQUEST["search"] ?? $_REQUEST["q"] ?? ""));
            $limit = min(30, max(1, (int)($_REQUEST["limit"] ?? 20)));
            if (strlen($q) < 1) {
                $data["students"] = [];
                $status = true;
            } else {
                $esc = addslashes($q);
                $tables = [
                    ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
                ];
                $columns = [
                    "s.id",
                    "s.user_id",
                    "s.index_number",
                    "CONCAT(COALESCE(s.lastname, ''), ' ', COALESCE(s.firstname, ''), ' ', COALESCE(s.othernames, '')) AS fullname",
                    "p.name AS program_name",
                    "s.allergy",
                    "s.insurance_number",
                ];
                $where = [
                    "s.approved = 1",
                    "(s.index_number LIKE '%{$esc}%' OR s.lastname LIKE '%{$esc}%' OR s.firstname LIKE '%{$esc}%' OR s.othernames LIKE '%{$esc}%')",
                ];
                $rows = fetchData($columns, $tables, $where, $limit);
                $list = is_array($rows) ? (isset($rows["id"]) ? [$rows] : $rows) : [];
                $data["students"] = [];
                foreach ($list as $row) {
                    $hist = fetchData(
                        "medical_conditions, allergies, medications, immunization_records, emergency_contacts",
                        "medical_histories",
                        ["student_id = " . (int)$row["id"]],
                        1
                    );
                    $data["students"][] = array_merge($row, is_array($hist) ? $hist : []);
                }
                $status = true;
            }
        }elseif($submit == "get_medical_student"){
            $uid = (int)($_REQUEST["user_id"] ?? 0);
            if ($uid < 1) {
                $errors["user_id"] = "Invalid user";
            } else {
                $st = fetchData("id, user_id, index_number, allergy, insurance_number", "students", ["user_id" => $uid], 1);
                if ($st) {
                    $hist = fetchData(
                        "*",
                        "medical_histories",
                        ["student_id = " . (int)$st["id"]],
                        1
                    );
                    $data["student"] = $st;
                    $data["history"] = is_array($hist) ? $hist : [];
                    $status = true;
                } else {
                    $errors["system_error"] = "Student not found";
                }
            }
        }elseif($submit == "fetch_disciplinary_records"){
            $search = trim((string)($_REQUEST["search"] ?? ""));
            $programId = (int)($_REQUEST["program_id"] ?? 0);
            $returnFilter = $_REQUEST["return_status"] ?? "";
            $tables = [
                ["join" => "disciplinary_records programs", "on" => "program_id id", "alias" => "d p"],
            ];
            $columns = [
                "d.id",
                "d.index_number",
                "d.fullname",
                "d.offense",
                "d.action_taken",
                "d.date_of_action",
                "d.return_date",
                "d.return_status",
                "p.name AS program_name",
            ];
            $where = [];
            if ($search !== "") {
                $esc = addslashes($search);
                $where[] = "(d.index_number LIKE '%{$esc}%' OR d.fullname LIKE '%{$esc}%' OR d.offense LIKE '%{$esc}%')";
            }
            if ($programId > 0) {
                $where[] = "d.program_id = {$programId}";
            }
            if ($returnFilter === "open") {
                $where[] = "d.return_status = 0";
            } elseif ($returnFilter === "closed") {
                $where[] = "d.return_status = 1";
            }
            $rows = fetchData($columns, $tables, $where, 100, order_by: "d.date_of_action", asc: false);
            $data["records"] = is_array($rows) ? (isset($rows["id"]) ? [$rows] : $rows) : [];
            $status = true;
        }elseif($submit == "resolve_disciplinary_record"){
            $id = (int)($_REQUEST["record_id"] ?? 0);
            if ($id < 1) {
                $errors["record_id"] = "Invalid record";
            } else {
                $orig = fetchData("*", "disciplinary_records", ["id" => $id], 1);
                if ($orig && update(
                    $orig,
                    ["return_status" => 1, "return_date" => date("Y-m-d")],
                    "disciplinary_records",
                    ["id"]
                ) === true) {
                    $status = true;
                    $data["message"] = "Record updated";
                } else {
                    $errors["system_error"] = "Update failed";
                }
            }
        }elseif($submit == "fetch_student_clearances"){
            require_once $_SERVER["DOCUMENT_ROOT"] . "/includes/clearance_departments.php";
            $sid = (int)($_REQUEST["student_id"] ?? 0);
            if ($sid < 1) {
                $errors["student_id"] = "Invalid student";
            } else {
                $rows = fetchData("*", "student_clearances", ["student_id = {$sid}"], 0);
                $byKey = [];
                if (is_array($rows)) {
                    $list = isset($rows["id"]) ? [$rows] : $rows;
                    foreach ($list as $r) {
                        $byKey[$r["department_key"]] = $r;
                    }
                }
                $out = [];
                foreach (clearance_department_definitions() as $key => $label) {
                    if (!in_array($key, allowed_clearance_department_keys(), true)) {
                        continue;
                    }
                    if (isset($byKey[$key])) {
                        $out[] = array_merge($byKey[$key], ["label" => $label]);
                    } else {
                        $out[] = [
                            "department_key" => $key,
                            "status" => default_clearance_status_for_department($key),
                            "cleared_by" => null,
                            "cleared_at" => null,
                            "notes" => null,
                            "label" => $label,
                        ];
                    }
                }
                $data["clearances"] = $out;
                $status = true;
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