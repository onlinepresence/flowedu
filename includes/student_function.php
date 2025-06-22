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

