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

        if($submit == "fetch_grade_points"){
            $grade_points = fetchData("*", "grade_points", [], 0, order_by: "points", asc: false);
            $data["grade_points"] = is_array($grade_points) ? $grade_points : [];
            $status = true;
        }
        
        elseif($submit == "fetch_results"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = [
                ["join" => "results students", "on" => "student_id id", "alias" => "r s"],
                ["join" => "results courses", "on" => "course_id id", "alias" => "r c"],
                ["join" => "results academic_sessions", "on" => "session_id id", "alias" => "r sess"]
            ];
            $columns = [
                "r.id", "r.student_id", "r.course_id", "r.session_id",
                "s.index_number", "CONCAT(s.lastname, ' ', s.othernames) AS student_name",
                "c.code AS course_code", "c.name AS course_name",
                "r.score", "r.grade", "r.grade_points",
                "sess.name AS session_name", "r.entered_date"
            ];

            $where = buildWhereClause($filters);
            
            $results = fetchData($columns, $tables, $where, $limit, offset: $offset);

            if($results){
                $data["results"] = $results;
                $data["total"] = (int) fetchData("COUNT(r.id) AS total", $tables, $where)["total"];
            } else {
                $data["results"] = [];
                $data["total"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "fetch_course_students"){
            $course_id = $_REQUEST["course_id"] ?? null;
            $session_id = $_REQUEST["session_id"] ?? null;
            $level = $_REQUEST["level"] ?? null;
            
            if(empty($course_id) || empty($session_id)){
                $errors["system_error"] = "Course ID and Session ID are required";
            } else {
                // Get course details
                $course = fetchData("*", "courses", ["id" => $course_id]);
                if(!$course){
                    $errors["system_error"] = "Course not found";
                } else {
                    // Fetch students for the course level and session
                    $where = ["approved = 1"];
                    if($level){
                        $where[] = "current_year = " . (int)$level;
                    }
                    
                    $tables = [
                        ["join" => "students programs", "on" => "program_id id", "alias" => "s p"]
                    ];
                    $columns = [
                        "s.id", "s.user_id", "s.index_number",
                        "CONCAT(s.lastname, ' ', s.othernames) AS fullname",
                        "s.current_year", "p.name AS program_name"
                    ];
                    
                    // Also fetch existing results
                    $students = fetchData($columns, $tables, $where, 0);
                    if($students){
                        foreach($students as &$student){
                            $existing_result = fetchData("*", "results", [
                                "student_id" => $student['id'],
                                "course_id" => $course_id,
                                "session_id" => $session_id
                            ]);
                            $student['existing_result'] = $existing_result;
                            $student['score'] = $existing_result['score'] ?? '';
                            $student['grade'] = $existing_result['grade'] ?? '';
                            $student['grade_points'] = $existing_result['grade_points'] ?? '';
                        }
                    }
                    
                    $data["students"] = is_array($students) ? $students : [];
                    $data["course"] = $course;
                    $status = true;
                }
            }
        }
        
        elseif($submit == "fetch_transcripts"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = [
                ["join" => "transcripts students", "on" => "student_id id", "alias" => "t s"],
                ["join" => "transcripts academic_sessions", "on" => "session_id id", "alias" => "t sess"]
            ];
            $columns = [
                "t.id", "t.student_id", "t.session_id",
                "s.index_number", "CONCAT(s.lastname, ' ', s.othernames) AS student_name",
                "sess.name AS session_name", "t.generated_date", "t.gpa", "t.cgpa", "t.status"
            ];

            $where = buildWhereClause($filters);
            
            $transcripts = fetchData($columns, $tables, $where, $limit, offset: $offset);

            if($transcripts){
                $data["transcripts"] = $transcripts;
                $data["total"] = (int) fetchData("COUNT(t.id) AS total", $tables, $where)["total"];
            } else {
                $data["transcripts"] = [];
                $data["total"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "calculate_gpa"){
            $student_id = $_REQUEST["student_id"] ?? null;
            $session_id = $_REQUEST["session_id"] ?? null;
            
            if(empty($student_id) || empty($session_id)){
                $errors["system_error"] = "Student ID and Session ID are required";
            } else {
                // Fetch all results for student in session
                $results = fetchData("*", "results", [
                    "student_id" => $student_id,
                    "session_id" => $session_id
                ], 0);
                
                if($results && is_array($results)){
                    $total_points = 0;
                    $total_credits = 0;
                    
                    foreach($results as $result){
                        // Get course credits
                        $course = fetchData("credits", "courses", ["id" => $result['course_id']]);
                        $credits = $course ? (float)$course['credits'] : 0;
                        $points = (float)($result['grade_points'] ?? 0);
                        
                        $total_points += $points * $credits;
                        $total_credits += $credits;
                    }
                    
                    $gpa = $total_credits > 0 ? ($total_points / $total_credits) : 0;
                    
                    $data["gpa"] = round($gpa, 2);
                    $data["total_credits"] = $total_credits;
                    $data["total_points"] = $total_points;
                    $status = true;
                } else {
                    $data["gpa"] = 0;
                    $data["total_credits"] = 0;
                    $data["total_points"] = 0;
                    $status = true;
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
