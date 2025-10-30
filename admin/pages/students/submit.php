<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    function buildWhereClause($filters) {
        $where = [];

        $mapping = [
            "level" => "current_year",
            "program" => "program_id",
            "department" => "department_id",
            "faculty" => "faculty_id"
        ];

        foreach ($mapping as $key => $column) {
            if (!empty($filters[$key])) {
                $where[] = "$column = '{$filters[$key]}'";
            }
        }

        return $where;
    }
    
    $errors = [];
    $request_from = $_SERVER["HTTP_REFERER"];
    $next_request = null;
    $_SESSION["old_input"] = $_REQUEST;

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
                "index_number", "CONCAT(lastname, ' ', othernames) AS fullname", "d.name as department_name", "p.name as program_name",
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
                    "index_number", "CONCAT(lastname, ' ', othernames) AS fullname", "d.name as department_name",
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
                $errors[] = $e->getMessage();
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