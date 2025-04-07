<?php
    if(true){

    }else{
        return [
            "profile" => [
                "text" => "My Profile",
                "link" => "/lecturer/profile",
                "icon" => "fas fa-user"
            ],
        
            "courses" => [
                "text" => "My Courses",
                "icon" => "fas fa-book-open",
                "group" => true,
                "items" => [
                    ["text" => "Courses Assigned", "url" => "/lecturer/courses"],
                    ["text" => "Course Materials", "url" => "/lecturer/courses/materials"],
                    ["text" => "Class Timetable", "url" => "/lecturer/timetable"]
                ]
            ],
        
            "students" => [
                "text" => "Students",
                "icon" => "fas fa-users",
                "group" => true,
                "items" => [
                    ["text" => "Student List", "url" => "/lecturer/students"],
                    ["text" => "Attendance", "url" => "/lecturer/attendance"],
                    ["text" => "Performance", "url" => "/lecturer/performance"]
                ]
            ],
        
            "assessments" => [
                "text" => "Assessments",
                "icon" => "fas fa-clipboard-list",
                "group" => true,
                "items" => [
                    ["text" => "Upload Results", "url" => "/lecturer/results/upload"],
                    ["text" => "Grade Submissions", "url" => "/lecturer/grades"],
                ]
            ],
        
            "communication" => [
                "text" => "Communication",
                "icon" => "fas fa-envelope",
                "group" => true,
                "items" => [
                    ["text" => "Announcements", "url" => "/lecturer/announcements"],
                    ["text" => "Messages", "url" => "/lecturer/messages"]
                ]
            ]
        ];
    }