<?php
    require_once($_SERVER["DOCUMENT_ROOT"]."/includes/session.php");

    $errors = [];
    $status = false;
    $data = [];
    $limit = 50;

    if(isset($_REQUEST["submit"])){
        $submit = $_REQUEST["submit"];

        if($submit == "fetch_evaluation_forms"){
            $filters = form_data();
            $page = (int)($filters["page"] ?? 1);
            $offset = $limit * ($page - 1); 
            $current_time_db = date('Y-m-d H:i:s'); 

            $tables = "evaluation_forms";
            $columns = ["*"];
            $where = buildWhereClause($filters); // Example: Handles search term filters

            // Add status filter logic
            if (!empty($filters['status'])) {                
                switch ($filters['status']) {
                    case 'active':
                        // is_active = 1 AND current time is between start_time and end_time
                        $where[] = create_where_from_array(["is_active" => 1]);
                        $where[] = create_where_from_array(["start_time" => $current_time_db], "<=");
                        $where[] = create_where_from_array(["end_time" => $current_time_db], ">=");
                        break;
                    case 'pending':
                        // Not yet started (start_time > current time)
                        $where[] = create_where_from_array(["start_time" => $current_time_db], ">");
                        break;
                    case 'closed':
                        // End time has passed
                        $where[] = create_where_from_array(["end_time" => $current_time_db], "<");
                        break;
                }
            }

            // Fetch paginated data
            $forms = fetchData($columns, $tables, $where, $limit, offset: $offset, order_by: "id", asc: false);

            $processed_forms = [];
            if(is_array($forms)){
                foreach($forms as $form){
                    $start_ts = strtotime($form['start_time']);
                    $end_ts = strtotime($form['end_time']);
                    $current_ts = time();
                    
                    // Determine Status and Color
                    $status_text = 'Pending';
                    $status_color = 'gray';

                    if ($form['is_active'] && $current_ts >= $start_ts && $current_ts <= $end_ts) {
                        $status_text = 'Active';
                        $status_color = 'green';
                    } elseif ($current_ts > $end_ts) {
                        $status_text = 'Closed';
                        $status_color = 'red';
                    }
                    
                    $form['status'] = $status_text;
                    $form['status_color'] = $status_color;

                    // Format dates for display
                    $form['start_date_formatted'] = date('M d, Y', $start_ts);
                    $form['end_date_formatted'] = date('M d, Y', $end_ts);
                    $form['start_time_formatted'] = date('H:i', $start_ts);
                    $form['end_time_formatted'] = date('H:i', $end_ts);
                    
                    // Format DATETIME for JS Modal inputs (Y-m-d\TH:i)
                    $form['start_datetime'] = date('Y-m-d\TH:i', $start_ts);
                    $form['end_datetime'] = date('Y-m-d\TH:i', $end_ts);
                    
                    $processed_forms[] = $form;
                }
                $data["forms"] = $processed_forms;
                $data["total"] = (int) fetchData("COUNT(id) AS total", $tables, $where)["total"];
                $status = true;
            } else {
                $data["forms"] = []; 
                $data["total"] = 0;
                $status = true;
            }
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