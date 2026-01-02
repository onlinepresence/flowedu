<?php
    function calculate_cgpa($courses) {
        $totalPoints = 0;
        $totalCredits = 0;
    
        foreach ($courses as $course) {
            $gradePoint = $course['grade_point'];
            $creditHours = $course['credit_hours'];
    
            $totalPoints += $gradePoint * $creditHours;
            $totalCredits += $creditHours;
        }
    
        return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;
    }

    function get_grade_points(){
        if(!isset($_SESSION["school_grade_points"])){

        }

        return $_SESSION["school_grade_points"];
    }

    function enrolment_year($currentLevel) {
        $currentYear = date("Y");
        $academicYearStart = 9; // Academic year in Ghana typically starts in September
        $currentMonth = date("n");

        // If the current month is before September, subtract 1 from the current year
        if ($currentMonth < $academicYearStart) {
            $currentYear--;
        }

        // Calculate the admission year based on the current level
        $admissionYear = $currentYear - (($currentLevel / 100) - 1);

        return $admissionYear;
    }
    
    function completion_year($currentLevel, $years = 4) {
        // Get the enrolment year using the enrolment_year function
        $enrolmentYear = enrolment_year($currentLevel);
    
        // Add the number of years the student is expected to be in school to the enrolment year
        $completionYear = $enrolmentYear + $years;
    
        return $completionYear;
    }

    /**
     * This function creates an evaluation response for a student
     */
    function create_evaluation_response($evaluation_form_id){
        $student = user();
        $response = null;

        // get all teachers teaching this student's courses
        // $teachers = fetchData("t.teacher_id", [
        //     "join" => "teacher_courses courses",
        //     "on" => "course_id id",
        //     "alias" => "t c",
        // ], ["t.program_level" => $student["current_year"], "c.program_id" => $student["program_id"]], 0, "AND");

        // get all teachers in this user's department
        $teachers = fetchData("user_id AS id, CONCAT(lastname, ' ', othernames) AS fullname", "teachers", ["department_id" => $student["department_id"]], 0);
        
        if($teachers){
            $teacher_list = array_column($teachers, "id");

            // get responses created for this evaluation form and extract the last teacher id
            $last_inserted_teacher = fetchData("teacher_id", "evaluation_responses", ["form_id" => $evaluation_form_id, "student_department_id" => $student["department_id"]], 1, asc: false)["teacher_id"] ?? false;

            // if response exists, get the last teacher id and create for the next teacher
            // if the teacher doesnt exist in the list, it means teachers for the specified class/department havent started yet
            if($last_inserted_teacher && in_array($last_inserted_teacher, $teacher_list)){
                $last_index = array_search($last_inserted_teacher, $teacher_list);
                $next_index = $last_index + 1;

                // check if there is a next teacher
                if(isset($teacher_list[$next_index])){
                    $next_teacher_id = $teacher_list[$next_index];
                }else{
                    // restart from top
                    $next_teacher_id = $teacher_list[0];
                }
            }else{
                $next_teacher_id = $teacher_list[0];
            }
            
            // generate a response code
            do {
                $response_code = "ER-".strtoupper(generate_unique_code(use_letters:false))."-".date("y");
                $existing_response = fetchData("id", "evaluation_responses", ["response_code" => $response_code]);
            } while ($existing_response);

            // create response entry
            $data = [
                "form_id" => $evaluation_form_id,
                "student_id" => $student["id"],
                "student_department_id" => $student["department_id"],
                "teacher_id" => $next_teacher_id,
                "response_code" => $response_code
            ];

            if(($response = data_insert("evaluation_responses", $data)) === true){
                $response = [
                    "response_code" => $response_code,
                    "teacher_id" => $next_teacher_id
                ];
            }
        }

        return $response;
    }

