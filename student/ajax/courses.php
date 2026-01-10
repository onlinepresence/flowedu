<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $request_from = $_SERVER["HTTP_REFERER"];
    $next_request = null;
    $_SESSION["old_input"] = $_REQUEST;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if ($submit == 'fetch_my_courses') {
            $program_id = (int)$_REQUEST['program_id'];
            $search = $_REQUEST['search'] ?? '';
            $limit = (int)($_REQUEST['limit'] ?? 10);
            $page = (int)($_REQUEST['page'] ?? 1);
            $offset = ($page - 1) * $limit;
        
            // Base WHERE clause
            $where = "program_id = $program_id";
            
            // Add search filter if provided
            if (!empty($search)) {
                $where .= " AND (name LIKE '%$search%' OR code LIKE '%$search%')";
            }
        
            // Fetch data using your fetchData helper
            $courses = fetchData(
                "*", 
                "courses", 
                $where, 
                limit: $limit, 
                offset: $offset, 
                order_by: "year_level, course_semester"
            );
        
            // Get total count for pagination UI
            $total_count = (int)fetchData("COUNT(id) as total", "courses", $where)['total'];

            $data = [
                "courses" => $courses, "total" => $total_count
            ];
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