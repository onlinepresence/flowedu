<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");
    
    $errors = [];
    $limit = 50;
    $request_from = $_SERVER["HTTP_REFERER"] ?? "";
    $next_request = null;
    $_SESSION["old_input"] = $_REQUEST;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "fetch_sessions"){
            $filters = form_data(exclude: ["submit", "response_type"]);
            $offset = $limit * (($filters["page"] ?? 1) - 1);

            $tables = "academic_sessions";
            $columns = ["*"];
            $where = buildWhereClause($filters);

            // Fetch sessions
            $sessions = fetchData($columns, $tables, $where, 0, offset: $offset, order_by: "start_date", asc: false);

            if(is_array($sessions)){
                // Fetch semesters for each session
                foreach($sessions as &$session){
                    $semesters = fetchData("*", "semesters", ["academic_session_id" => $session['id']], 0, order_by: "start_date", asc: true);
                    $session['semesters'] = is_array($semesters) ? $semesters : [];
                }
                $data["sessions"] = $sessions;
                $data["total"] = (int) fetchData("COUNT(id) AS total", $tables, $where)["total"];
                $status = true;
            } else {
                $data["sessions"] = [];
                $data["total"] = 0;
                $status = true;
            }
        } elseif ($submit == "add_session") {
            $input = form_data();
            
            // Validate input
            if(empty($input['name'])) $errors['name'] = "Session name is required";
            if(empty($input['start_date'])) $errors['start_date'] = "Start date is required";
            if(empty($input['end_date'])) $errors['end_date'] = "End date is required";
            
            // Validate date range
            if(!empty($input['start_date']) && !empty($input['end_date'])){
                if(strtotime($input['end_date']) <= strtotime($input['start_date'])){
                    $errors['end_date'] = "End date must be after start date";
                }
            }
            
            // Validate semesters if provided
            if(isset($input['semesters']) && is_array($input['semesters'])){
                foreach($input['semesters'] as $index => $semester){
                    if(!empty($semester['start_date']) && !empty($semester['end_date'])){
                        // Check semester dates are within session dates
                        if(strtotime($semester['start_date']) < strtotime($input['start_date'])){
                            $errors["semesters"][$index]['start_date'] = "Semester start date must be within session dates";
                        }
                        if(strtotime($semester['end_date']) > strtotime($input['end_date'])){
                            $errors["semesters"][$index]['end_date'] = "Semester end date must be within session dates";
                        }
                        if(strtotime($semester['end_date']) <= strtotime($semester['start_date'])){
                            $errors["semesters"][$index]['end_date'] = "Semester end date must be after start date";
                        }
                    }
                }
            }
            
            if(empty($errors)){
                // Insert Session
                $session_data = [
                    'name' => $input['name'],
                    'start_date' => $input['start_date'],
                    'end_date' => $input['end_date'],
                    'is_current' => isset($input['is_current']) ? 1 : 0
                ];
                
                // If setting as current, unset others
                if($session_data['is_current']){
                    // Update all other sessions to set is_current = 0
                    $existing_current = fetchData("id", "academic_sessions", ["is_current" => 1], 0);
                    if(is_array($existing_current) && !empty($existing_current)){
                        foreach($existing_current as $existing){
                            update(['id' => $existing['id']], ['is_current' => 0], 'academic_sessions', ['id']);
                        }
                    }
                }

                if(data_insert('academic_sessions', $session_data)){
                    $session_id = db_last_insert_id();
                    
                    // Insert Semesters
                    if(isset($input['semesters']) && is_array($input['semesters'])){
                        foreach($input['semesters'] as $semester){
                            if(!empty($semester['name']) && !empty($semester['start_date']) && !empty($semester['end_date'])){
                                $semester_data = [
                                    'academic_session_id' => $session_id,
                                    'name' => $semester['name'],
                                    'start_date' => $semester['start_date'],
                                    'end_date' => $semester['end_date'],
                                ];
                                data_insert('semesters', $semester_data);
                            }
                        }
                    }
                    $status = true;
                    $data["message"] = "Academic session created successfully";
                } else {
                    $errors['system_error'] = "Failed to create academic session";
                }
            }
        } elseif ($submit == "update_session") {
            $input = form_data();
            
            if(empty($input['id'])) $errors['id'] = "Session ID is required";
            
            // Validate input
            if(empty($input['name'])) $errors['name'] = "Session name is required";
            if(empty($input['start_date'])) $errors['start_date'] = "Start date is required";
            if(empty($input['end_date'])) $errors['end_date'] = "End date is required";
            
            // Validate date range
            if(!empty($input['start_date']) && !empty($input['end_date'])){
                if(strtotime($input['end_date']) <= strtotime($input['start_date'])){
                    $errors['end_date'] = "End date must be after start date";
                }
            }
            
            // Validate semesters if provided (before updating session)
            if(isset($input['semesters']) && is_array($input['semesters'])){
                foreach($input['semesters'] as $index => $semester){
                    if(!empty($semester['start_date']) && !empty($semester['end_date'])){
                        // Check semester dates are within session dates
                        if(!empty($input['start_date']) && strtotime($semester['start_date']) < strtotime($input['start_date'])){
                            $errors["semesters"][$index]['start_date'] = "Semester start date must be within session dates";
                        }
                        if(!empty($input['end_date']) && strtotime($semester['end_date']) > strtotime($input['end_date'])){
                            $errors["semesters"][$index]['end_date'] = "Semester end date must be within session dates";
                        }
                        if(strtotime($semester['end_date']) <= strtotime($semester['start_date'])){
                            $errors["semesters"][$index]['end_date'] = "Semester end date must be after start date";
                        }
                    }
                }
            }
            
            if(empty($errors)){
                $session_id = $input['id'];
                $session_data = [
                    'name' => $input['name'],
                    'start_date' => $input['start_date'],
                    'end_date' => $input['end_date'],
                    'is_current' => isset($input['is_current']) ? 1 : 0
                ];
                
                // If setting as current, unset others (but exclude current session)
                if($session_data['is_current']){
                    // Update all other sessions to set is_current = 0
                    $existing_current = fetchData("id", "academic_sessions", ["is_current" => 1], 0);
                    if(is_array($existing_current) && !empty($existing_current)){
                        foreach($existing_current as $existing){
                            // Don't update the current session being edited
                            if($existing['id'] != $session_id){
                                update(['id' => $existing['id']], ['is_current' => 0], 'academic_sessions', ['id']);
                            }
                        }
                    }
                }

                update(['id' => $session_id], $session_data, 'academic_sessions', ['id']);
                
                // Handle Semesters (Sync: Update existing, Add new, Delete removed)
                if(isset($input['semesters']) && is_array($input['semesters'])){

                    foreach($input['semesters'] as $semester){
                        if(!empty($semester['id'])){
                            // Update existing
                            if(!empty($semester['name']) && !empty($semester['start_date']) && !empty($semester['end_date'])){
                                $sem_data = [
                                    'name' => $semester['name'],
                                    'start_date' => $semester['start_date'],
                                    'end_date' => $semester['end_date'],
                                ];
                                update(['id' => $semester['id']], $sem_data, 'semesters', ['id']);
                            }
                        } else {
                            // Add new
                            if(!empty($semester['name']) && !empty($semester['start_date']) && !empty($semester['end_date'])){
                                $sem_data = [
                                    'academic_session_id' => $session_id,
                                    'name' => $semester['name'],
                                    'start_date' => $semester['start_date'],
                                    'end_date' => $semester['end_date'],
                                ];
                                data_insert('semesters', $sem_data);
                            }
                        }
                    }
                }
                
                // Handle deleted semesters if IDs are sent
                if(isset($input['deleted_semesters']) && is_array($input['deleted_semesters'])){
                    foreach($input['deleted_semesters'] as $sem_id){
                        delete('semesters', ['id' => $sem_id]);
                    }
                }

                $status = true;
                $data["message"] = "Academic session updated successfully";
            }
        } elseif ($submit == "delete_session") {
            $input = form_data();
            if(!empty($input['id'])){
                // Check if session has associated data before deleting
                // For now, we'll allow deletion (cascade will handle semesters)
                if(delete('academic_sessions', ['id' => $input['id']])){
                    $status = true;
                    $data["message"] = "Session deleted successfully";
                } else {
                    $errors['system_error'] = "Failed to delete session. It may have associated data.";
                }
            } else {
                $errors['id'] = "Session ID is required";
            }
        }
    }

    if(isset($_REQUEST["response_type"]) && $_REQUEST["response_type"] == "json"){
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
?>
