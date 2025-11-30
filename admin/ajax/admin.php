<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    $errors = [];
    $status = false;
    $data = [];
    $limit = 50;

    if(isset($_REQUEST["submit"]) && $_REQUEST["submit"] == "fetch_admins"){
        $filters = form_data();
        $offset = $limit * ($filters["page"] - 1); 

        // Assuming admins are users with a specific 'type' or 'role_id', e.g., role_id = 2
        // We also assume a 'roles' table for 'admin_type' display name.
        
        // Example joins: users (u) joins to roles (r)
        $tables = [
            ["join" => "admins users", "on" => "user_id id", "alias" => "a u"],
            ["join" => "admins user_roles", "on" => "type id", "alias" => "a r"],
        ]; 
        
        // Columns must match the keys in your pagination_script mapping
        $columns = [
            "u.id",          // Maps to ID
            "CONCAT(a.lastname, ' ', a.othernames) AS full_name", // Maps to NAME
            "u.email",       // Maps to EMAIL
            "r.display_name AS admin_type", // Maps to TYPE
        ];

        // Filter to show only 'Admin' roles (assuming role_id = 2 is Admin)
        $where = buildWhereClause($filters); 
        $where[] = "a.type = 2";

        // Fetch paginated data
        $data["admins"] = fetchData($columns, $tables, $where, $limit, offset: $offset);

        if($data["admins"]){
            $data["total"] = (int) fetchData("COUNT(u.id) AS total", $tables, $where)["total"];
            $status = true;
        } else {
            $data["admins"] = []; 
            $data["total"] = 0;
            $status = true;
        }
    }

    // --- JSON RESPONSE (Standardized) ---
    if($_REQUEST["response_type"] == "json"){
        header("Content-type: application/json");
        echo json_encode([
            "errors" => $errors,
            "old_input" => $_REQUEST,
            "status" => $status,
            "data" => $data
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