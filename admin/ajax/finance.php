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

        if($submit == "fetch_fee_structures"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = [
                ["join" => "fee_structures programs", "on" => "program_id id", "alias" => "f p"],
                ["join" => "fee_structures academic_sessions", "on" => "session_id id", "alias" => "f s"]
            ];
            $columns = [
                "f.id", "f.program_id", "f.level", "f.session_id",
                "p.name AS program_name", "s.name AS session_name",
                "f.tuition_fee", "f.library_fee", "f.lab_fee",
                "f.medical_fee", "f.sports_fee", "f.examination_fee",
                "f.total_amount", "f.created_at"
            ];

            $where = buildWhereClause($filters);
            
            $fee_structures = fetchData($columns, $tables, $where, $limit, offset: $offset);

            if($fee_structures){
                $data["fee_structures"] = $fee_structures;
                $data["total"] = (int) fetchData("COUNT(f.id) AS total", $tables, $where)["total"];
            } else {
                $data["fee_structures"] = [];
                $data["total"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "fetch_payments"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = [
                ["join" => "payments students", "on" => "student_id id", "alias" => "p s"],
                ["join" => "payments fee_structures", "on" => "fee_structure_id id", "alias" => "p f"]
            ];
            $columns = [
                "p.id", "p.student_id", "p.fee_structure_id",
                "s.index_number", "CONCAT(s.lastname, ' ', s.othernames) AS student_name",
                "p.amount_paid", "p.payment_method", "p.payment_date",
                "p.reference_number", "p.status", "p.received_by"
            ];

            $where = buildWhereClause($filters);
            
            $payments = fetchData($columns, $tables, $where, $limit, offset: $offset);

            if($payments){
                $data["payments"] = $payments;
                $data["total"] = (int) fetchData("COUNT(p.id) AS total", $tables, $where)["total"];
                // Calculate total amount
                $total_result = fetchData("SUM(amount_paid) AS total_amount", $tables, $where);
                $data["total_amount"] = $total_result ? (float)$total_result['total_amount'] : 0;
            } else {
                $data["payments"] = [];
                $data["total"] = 0;
                $data["total_amount"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "fetch_outstanding_fees"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = [
                ["join" => "fee_structures programs", "on" => "program_id id", "alias" => "f p"],
                ["join" => "fee_structures academic_sessions", "on" => "session_id id", "alias" => "f s"]
            ];
            $columns = [
                "f.id AS fee_structure_id", "f.program_id", "f.level", "f.session_id",
                "p.name AS program_name", "s.name AS session_name",
                "f.total_amount", "f.level"
            ];

            $where = buildWhereClause($filters);
            
            $fee_structures = fetchData($columns, $tables, $where, 0);
            
            if($fee_structures){
                $outstanding = [];
                foreach($fee_structures as $fee_structure){
                    // Get all students for this fee structure
                    $student_where = [
                        "program_id = " . $fee_structure['program_id'],
                        "current_year = " . $fee_structure['level'],
                        "approved = 1"
                    ];
                    $students = fetchData("id, index_number, lastname, othernames", "students", $student_where, 0);
                    
                    if($students){
                        foreach($students as $student){
                            // Calculate paid amount
                            $total_paid = fetchData(
                                "SUM(amount_paid) AS total_paid",
                                "payments",
                                [
                                    "student_id" => $student['id'],
                                    "fee_structure_id" => $fee_structure['fee_structure_id']
                                ]
                            );
                            $paid = $total_paid ? (float)$total_paid['total_paid'] : 0;
                            $outstanding_amount = $fee_structure['total_amount'] - $paid;
                            
                            if($outstanding_amount > 0){
                                $outstanding[] = [
                                    "student_id" => $student['id'],
                                    "index_number" => $student['index_number'],
                                    "student_name" => $student['lastname'] . " " . $student['othernames'],
                                    "program_name" => $fee_structure['program_name'],
                                    "session_name" => $fee_structure['session_name'],
                                    "total_fee" => $fee_structure['total_amount'],
                                    "amount_paid" => $paid,
                                    "outstanding" => $outstanding_amount
                                ];
                            }
                        }
                    }
                }
                
                // Paginate
                $total = count($outstanding);
                $data["outstanding"] = array_slice($outstanding, $offset, $limit);
                $data["total"] = $total;
                $data["total_outstanding"] = array_sum(array_column($outstanding, 'outstanding'));
            } else {
                $data["outstanding"] = [];
                $data["total"] = 0;
                $data["total_outstanding"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "fetch_scholarships"){
            $filters = form_data();
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $columns = [
                "id", "name", "type", "amount", "description",
                "status", "created_at", "created_by"
            ];

            $where = buildWhereClause($filters);
            
            $scholarships = fetchData($columns, "scholarships", $where, $limit, offset: $offset);

            if($scholarships){
                // Get student count for each scholarship
                foreach($scholarships as &$scholarship){
                    $count = fetchData(
                        "COUNT(id) AS total",
                        "scholarship_recipients",
                        ["scholarship_id" => $scholarship['id']]
                    );
                    $scholarship['recipient_count'] = $count ? (int)$count['total'] : 0;
                }
                
                $data["scholarships"] = $scholarships;
                $data["total"] = (int) fetchData("COUNT(id) AS total", "scholarships", $where)["total"];
            } else {
                $data["scholarships"] = [];
                $data["total"] = 0;
            }

            $status = true;
        }
        
        elseif($submit == "fetch_scholarship_recipients"){
            $scholarship_id = $_REQUEST["scholarship_id"] ?? null;
            
            if(empty($scholarship_id)){
                $errors["system_error"] = "Scholarship ID is required";
            } else {
                $tables = [
                    ["join" => "scholarship_recipients students", "on" => "student_id id", "alias" => "sr s"]
                ];
                $columns = [
                    "sr.id", "sr.student_id", "sr.amount_awarded", "sr.award_date",
                    "sr.status", "s.index_number",
                    "CONCAT(s.lastname, ' ', s.othernames) AS student_name"
                ];
                
                $recipients = fetchData($columns, $tables, ["sr.scholarship_id" => $scholarship_id], 0);
                $data["recipients"] = is_array($recipients) ? $recipients : [];
                $status = true;
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
