<?php
    list($index_number, $guardian_status, $user_id) = $params;
    $last_request = $_SERVER["HTTP_REFERER"];
    $student = get_user_details($user_id);
    $program = programs($student["program_id"]);

    $error = "";

    if($student["index_number"] != $index_number){
        $error = "Student information is invalid";
    }elseif($guardian_status == 0){
        $error = "Student cannot be approved. Guardian information not completed";
    }elseif($student["approved"]){
        $error = "Student has already been approved";
    }else{
        $data = [
            "admission_index" => $index_number, "approved" => 1, 
            "department_id" => $program["department_id"]
        ];
        update($student, $data, "students", ["user_id"]);
    }

    if($error){
        $_SESSION["errors"]["system_message"] = $error;
    }else{
        $_SESSION["system_message"] = $student["lastname"]."(".$student["index_number"].") has been approved";
    }

    header("location: $last_request");