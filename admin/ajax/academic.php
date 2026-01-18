<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $status = false;
    $data = [];
    $limit = 50;
    $request_from = $_SERVER["HTTP_REFERER"] ?? "";
    $_SESSION["old_input"] = $_REQUEST;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "fetch_timetables"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = [
                ["join" => "timetables programs", "on" => "program_id id", "alias" => "t p"],
                ["join" => "timetables academic_sessions", "on" => "session_id id", "alias" => "t s"]
            ];
            $columns = [
                "t.id", "t.program_id", "t.level", "t.session_id",
                "p.name AS program_name", "s.name AS session_name",
                "t.created_at", "t.created_by"
            ];

            $where = buildWhereClause($filters);
            
            $timetables = fetchData($columns, $tables, $where, $limit, offset: $offset);

            if($timetables){
                // Fetch class count for each timetable
                foreach($timetables as &$timetable){
                    $class_count = fetchData("COUNT(id) AS total", "timetable_classes", ["timetable_id" => $timetable['id']]);
                    $timetable['class_count'] = $class_count ? (int)$class_count['total'] : 0;
                }
                
                $data["timetables"] = $timetables;
                $data["total"] = (int) fetchData("COUNT(t.id) AS total", $tables, $where)["total"];
            } else {
                $data["timetables"] = [];
                $data["total"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "fetch_timetable_classes"){
            $timetable_id = $_REQUEST["timetable_id"] ?? null;
            
            if(empty($timetable_id)){
                $errors["system_error"] = "Timetable ID is required";
            } else {
                $classes = fetchData("*", "timetable_classes", ["timetable_id" => $timetable_id], 0, order_by: "day, start_time", asc: true);
                $data["classes"] = is_array($classes) ? $classes : [];
                $status = true;
            }
        }
        
        elseif($submit == "delete_timetable_class"){
            $class_id = $_REQUEST["class_id"] ?? null;
            
            if(empty($class_id)){
                $errors["system_error"] = "Class ID is required";
            } else {
                $class = fetchData("id", "timetable_classes", ["id" => $class_id]);
                if($class){
                    if(delete($class, "timetable_classes", ["id"])){
                        $status = true;
                        $data["message"] = "Class removed from timetable successfully";
                    } else {
                        $errors["system_error"] = "Failed to delete class";
                    }
                } else {
                    $errors["system_error"] = "Class not found";
                }
            }
        }
        
        elseif($submit == "load_timetable" || $submit == "create_timetable"){
            $input = form_data();
            
            if(empty($input['program_id'])){
                $errors["program_id"] = "Program is required";
            }
            if(empty($input['level'])){
                $errors["level"] = "Level is required";
            }
            if(empty($input['session_id'])){
                $errors["session_id"] = "Academic session is required";
            }
            
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
                    $data["message"] = "Timetable loaded successfully";
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
    }else{
        unset($_SESSION["old_input"]);
        header("location: $request_from");
    }
