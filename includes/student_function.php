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