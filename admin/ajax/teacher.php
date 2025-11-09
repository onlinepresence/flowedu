<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $request_from = $_SERVER["HTTP_REFERER"];
    $next_request = null;
    $_SESSION["old_input"] = $_REQUEST;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "fetch_teachers"){
            // Teachers usually don't need complex filters, but we fetch the page
            $filters = form_data();
            $offset = 50 * ($filters["page"] - 1); // Use your standard limit of 50
    
            // We assume teachers table is named 'users' with type='teacher' or 'teachers'
            // I will assume the table is named 'users' and you join to a 'teachers' profile table
            $tables = [
                ["join" => "users teachers", "on" => "id user_id", "alias" => "u t"],
                ["join" => "teachers departments", "on" => "department_id id", "alias" => "t d"]
            ]; 
            $columns = [
                "u.id AS user_id", // Essential for the delete button to work
                "u.email", "profile_pic", "phone_number as phone", "staff_id", "d.name AS department", "employment_type",
                // Concatenate names or select them directly if the names are in the 'users' table
                "CONCAT(t.lastname, ' ', t.othernames) AS fullname", 
                "t.ghana_card"
            ];
    
            $where = buildWhereClause($filters);
            $where[] = "u.type = 'teacher'"; // Only fetch records with user type 'teacher'
    
            // Fetch paginated data
            $data["teachers"] = fetchData($columns, $tables, $where, 50, offset: $offset, join_type: "LEFT");
    
            if($data["teachers"]){
                $data["total"] = (int) fetchData("COUNT(u.id) AS total", $tables, $where, join_type: "left")["total"];
            }else{
                $data["teachers"] = []; 
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