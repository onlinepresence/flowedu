<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $limit = 50;
    $request_from = $_SERVER["HTTP_REFERER"];
    $next_request = null;
    $_SESSION["old_input"] = $_REQUEST;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "fetch_faculties"){ // Matches the "submit" value in pagination_script
            $filters = form_data();
            $offset = $limit * ($filters["page"] - 1); 
    
            // Define table joins: faculties table needs to be joined to users and teachers tables to get the Dean's name
            $tables = [
                // Join faculties (f) to teachers (t) on dean_id = user_id (optional join since dean_id can be NULL)
                ["join" => "faculties teachers", "on" => "dean_id user_id", "alias" => "f t", "join_type" => "LEFT"],
            ]; 
            
            // Columns must match the keys in your pagination_script mapping
            $columns = [
                "f.id", 
                "f.name", // Maps to NAME
                "f.dean_id", // Maps to DEAN_ID (crucial for edit form pre-fill)
                // Dean's full name, handling null case for display
                "IF(f.dean_id IS NOT NULL, CONCAT(t.lastname, ' ', t.othernames), 'Not Set') AS dean_name" // Maps to DEAN_NAME
            ];
    
            // Since we don't have filters on the frontend yet, the $where clause is simple
            $where = buildWhereClause($filters); 
    
            // Fetch paginated data
            $data["faculties"] = fetchData($columns, $tables, $where, $limit, offset: $offset, join_type: "LEFT");
    
            if($data["faculties"]){
                $data["total"] = (int) fetchData("COUNT(f.id) AS total", $tables, $where)["total"];
            }else{
                $data["faculties"] = []; 
                $data["total"] = 0;
            }
            $status = true;
        }elseif($submit == "fetch_departments"){
            $filters = form_data();
            $offset = $limit * ($filters["page"] - 1); 

            // Define table joins: departments (d) joins to faculties (f) and teachers (t)
            $tables = [
                ["join" => "departments faculties", "on" => "faculty_id id", "alias" => "d f"],
                ["join" => "departments teachers", "on" => "hod user_id", "alias" => "d t"], 
            ]; 
            
            // Columns must match the keys in your pagination_script mapping
            $columns = [
                "d.id", 
                "d.name", // Maps to NAME
                "d.faculty_id", // Maps to FACULTY_ID
                "f.name AS faculty_name", // Maps to FACULTY_NAME
                "d.hod", // Maps to HOD_ID
                "IF(d.hod IS NOT NULL, CONCAT(t.lastname, ' ', t.othernames), 'Not Set') AS hod_name" // Maps to HOD_NAME
            ];

            $where = buildWhereClause($filters); 

            // Fetch paginated data
            $data["departments"] = fetchData($columns, $tables, $where, 50, offset: $offset, join_type: "LEFT");

            if($data["departments"]){
                $data["total"] = (int) fetchData("COUNT(d.id) AS total", $tables, $where)["total"];
                $status = true;
            } else {
                $data["departments"] = []; 
                $data["total"] = 0;
                $status = true;
            }
        }elseif($submit == "fetch_programs"){
            $filters = form_data();
            $offset = $limit * ($filters["page"] - 1);
        
            // Define all table joins
            $tables = [
                ["join" => "programs departments", "on" => "department_id id", "alias" => "p d"]
            ];
        
            // Columns required by frontend
            $columns = [
                "p.id", 
                "p.name",          // Maps to NAME
                "p.certificate",   // Maps to CERTIFICATE
                "p.cost",          // Maps to COST
                "p.department_id", // Maps to DEPARTMENT_ID
                "d.name AS department_name" // Maps to DEPARTMENT_NAME
            ];
        
            // Filters / Where clause
            $where = buildWhereClause($filters);
        
            // Fetch data
            $data["programs"] = fetchData($columns, $tables, $where, $limit, offset: $offset, join_type: "LEFT");
        
            if ($data["programs"]) {
                $data["total"] = (int) fetchData("COUNT(p.id) AS total", $tables, $where, join_type: "LEFT")["total"];
            }  else {
                $data["programs"] = [];
                $data["total"] = 0;
            }
        
            $status = true;

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