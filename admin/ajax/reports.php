<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $status = false;
    $data = [];
    $request_from = $_SERVER["HTTP_REFERER"] ?? "";
    $_SESSION["old_input"] = $_REQUEST;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "generate_academic_report"){
            $filters = form_data();
            
            $where = [];
            if(!empty($filters['session_id'])){
                $where[] = "r.session_id = " . (int)$filters['session_id'];
            }
            if(!empty($filters['program_id'])){
                $where[] = "s.program_id = " . (int)$filters['program_id'];
            }
            if(!empty($filters['level'])){
                $where[] = "s.current_year = " . (int)$filters['level'];
            }
            
            $tables = [
                ["join" => "results students", "on" => "student_id id", "alias" => "r s"],
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
                ["join" => "results courses", "on" => "course_id id", "alias" => "r c"]
            ];
            
            $columns = [
                "s.index_number", "CONCAT(s.lastname, ' ', s.othernames) AS student_name",
                "p.name AS program_name", "s.current_year",
                "c.code AS course_code", "c.name AS course_name",
                "r.score", "r.grade", "r.grade_points"
            ];
            
            $results = fetchData($columns, $tables, $where, 0);
            
            if($results){
                // Organize by student
                $student_reports = [];
                foreach($results as $result){
                    $index = $result['index_number'];
                    if(!isset($student_reports[$index])){
                        $student_reports[$index] = [
                            "student_name" => $result['student_name'],
                            "program_name" => $result['program_name'],
                            "current_year" => $result['current_year'],
                            "courses" => []
                        ];
                    }
                    $student_reports[$index]['courses'][] = [
                        "course_code" => $result['course_code'],
                        "course_name" => $result['course_name'],
                        "score" => $result['score'],
                        "grade" => $result['grade'],
                        "grade_points" => $result['grade_points']
                    ];
                }
                
                $data["reports"] = array_values($student_reports);
                $data["total_students"] = count($student_reports);
            } else {
                $data["reports"] = [];
                $data["total_students"] = 0;
            }
            
            $status = true;
        }
        
        elseif($submit == "generate_payment_report"){
            $filters = form_data();
            
            $where = [];
            if(!empty($filters['session_id'])){
                $where[] = "p.session_id = " . (int)$filters['session_id'];
            }
            if(!empty($filters['date_from'])){
                $where[] = "p.payment_date >= '" . $filters['date_from'] . "'";
            }
            if(!empty($filters['date_to'])){
                $where[] = "p.payment_date <= '" . $filters['date_to'] . "'";
            }
            
            $tables = [
                ["join" => "payments students", "on" => "student_id id", "alias" => "p s"],
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"]
            ];
            
            $columns = [
                "p.id", "p.payment_date", "p.amount_paid", "p.payment_method",
                "p.reference_number", "s.index_number",
                "CONCAT(s.lastname, ' ', s.othernames) AS student_name",
                "p.name AS program_name"
            ];
            
            $payments = fetchData($columns, $tables, $where, 0, order_by: "p.payment_date", asc: false);
            
            if($payments){
                $total_amount = array_sum(array_column($payments, 'amount_paid'));
                
                $data["payments"] = $payments;
                $data["total_count"] = count($payments);
                $data["total_amount"] = $total_amount;
                
                // Group by payment method
                $by_method = [];
                foreach($payments as $payment){
                    $method = $payment['payment_method'] ?? 'Unknown';
                    if(!isset($by_method[$method])){
                        $by_method[$method] = ['count' => 0, 'amount' => 0];
                    }
                    $by_method[$method]['count']++;
                    $by_method[$method]['amount'] += $payment['amount_paid'];
                }
                $data["by_method"] = $by_method;
            } else {
                $data["payments"] = [];
                $data["total_count"] = 0;
                $data["total_amount"] = 0;
                $data["by_method"] = [];
            }
            
            $status = true;
        }
        
        elseif($submit == "generate_attendance_report"){
            $filters = form_data();
            
            $where = [];
            if(!empty($filters['session_id'])){
                $where[] = "a.session_id = " . (int)$filters['session_id'];
            }
            if(!empty($filters['program_id'])){
                $where[] = "s.program_id = " . (int)$filters['program_id'];
            }
            if(!empty($filters['level'])){
                $where[] = "s.current_year = " . (int)$filters['level'];
            }
            
            $tables = [
                ["join" => "attendance students", "on" => "student_id id", "alias" => "a s"],
                ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
                ["join" => "attendance courses", "on" => "course_id id", "alias" => "a c"]
            ];
            
            $columns = [
                "s.index_number", "CONCAT(s.lastname, ' ', s.othernames) AS student_name",
                "p.name AS program_name", "s.current_year",
                "c.code AS course_code", "c.name AS course_name",
                "a.attendance_date", "a.status", "a.remark"
            ];
            
            $attendance_records = fetchData($columns, $tables, $where, 0);
            
            if($attendance_records){
                // Organize by student and course
                $student_attendance = [];
                foreach($attendance_records as $record){
                    $key = $record['index_number'] . "_" . $record['course_code'];
                    if(!isset($student_attendance[$key])){
                        $student_attendance[$key] = [
                            "student_name" => $record['student_name'],
                            "program_name" => $record['program_name'],
                            "course_code" => $record['course_code'],
                            "course_name" => $record['course_name'],
                            "present" => 0,
                            "absent" => 0,
                            "late" => 0,
                            "excused" => 0
                        ];
                    }
                    
                    $status_lower = strtolower($record['status'] ?? '');
                    if($status_lower == 'present'){
                        $student_attendance[$key]['present']++;
                    } elseif($status_lower == 'absent'){
                        $student_attendance[$key]['absent']++;
                    } elseif($status_lower == 'late'){
                        $student_attendance[$key]['late']++;
                    } elseif($status_lower == 'excused'){
                        $student_attendance[$key]['excused']++;
                    }
                }
                
                // Calculate percentages
                foreach($student_attendance as &$attendance){
                    $total = $attendance['present'] + $attendance['absent'] + $attendance['late'] + $attendance['excused'];
                    $attendance['total'] = $total;
                    $attendance['attendance_rate'] = $total > 0 ? round(($attendance['present'] / $total) * 100, 2) : 0;
                }
                
                $data["attendance"] = array_values($student_attendance);
                $data["total_students"] = count(array_unique(array_column($attendance_records, 'index_number')));
            } else {
                $data["attendance"] = [];
                $data["total_students"] = 0;
            }
            
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
    }else{
        unset($_SESSION["old_input"]);
        header("location: $request_from");
    }
