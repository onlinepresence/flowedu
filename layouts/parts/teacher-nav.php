<?php
    if(empty(user()["username"])){
        return [
            "profile" => [
                "text" => "Setup Profile",
                "link" => "/teacher/setup",
                "icon" => "fas fa-user"
            ],
        ];
    }else{
        return [
            "dashboard" => [
                "text" => "My Dashboard",
                "link" => "/teacher/dashboard",
                "icon" => "fas fa-compass"
            ],
            "profile" => [
                "text" => "My Profile",
                "link" => "/teacher/profile",
                "icon" => "fas fa-user"
            ],
        
            "courses" => [
                "text" => "My Courses",
                "icon" => "fas fa-book-open",
                "group" => true,
                "items" => [
                    ["text" => "Courses Assigned", "url" => "/teacher/courses"],
                    ["text" => "Course Materials", "url" => "/teacher/courses/materials"],
                    ["text" => "Class Timetable", "url" => "/teacher/timetable"]
                ]
            ],
        
            "students" => [
                "text" => "Students",
                "icon" => "fas fa-users",
                "group" => true,
                "items" => [
                    ["text" => "Student List", "url" => "/teacher/students"],
                    ["text" => "Attendance", "url" => "/teacher/attendance"],
                    ["text" => "Performance", "url" => "/teacher/performance"]
                ]
            ],
        
            "assessments" => [
                "text" => "Assessments",
                "icon" => "fas fa-clipboard-list",
                "group" => true,
                "items" => [
                    ["text" => "Upload Results", "url" => "/teacher/results/upload"],
                    ["text" => "Grade Submissions", "url" => "/teacher/grades"],
                ]
            ],
        
            "communication" => [
                "text" => "Communication",
                "icon" => "fas fa-envelope",
                "group" => true,
                "items" => [
                    ["text" => "Announcements", "url" => "/teacher/announcements"],
                    ["text" => "Messages", "url" => "/teacher/messages"]
                ]
            ]
        ];
    }