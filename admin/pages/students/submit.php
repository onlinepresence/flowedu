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

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];
        $errors = [];
        $request_from = $_SERVER["HTTP_REFERER"];
        $next_request = null;
        $_SESSION["old_input"] = $_REQUEST;

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

            $data["students"] = fetchData($columns, $tables, $where, 50, offset: $offset);
            $data["total"] = (int) fetchData("COUNT(index_number) AS total", $tables, $where)["total"];

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