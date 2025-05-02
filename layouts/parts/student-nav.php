<?php
    if(!user()["approved"] || user()["is_new"]){
        return [
            "personal" => [
                "text" => "Personal Information",
                "link" => "/student-setup/personal",
                "icon" => "fas fa-address-card"
            ],
            "guardian_info" => [
                "text" => "Parent/Guardian Information",
                "link" => "/student-setup/guardian",
                "icon" => "fas fa-user-shield"
            ],
            "approved" => [
                "text" => "Admission Status",
                "link" => "/student-setup/status",
                "icon" => "fas ".(user()["approved"] ? "fa-user-check" : (is_null(user()["is_new"]) ? "fa-user-pen" : "fa-user-clock"))
            ],
            "delete-account" => [
                "text" => "Cancel Registration",
                "link" => "/student-setup/delete",
                "icon" => "fas fa-trash-can"
            ]
        ];
    }else{
        return [
            "dashboard" => [
                "text" => "Dashboard",
                "link" => "/student/dashboard",
                "icon" => "fas fa-compass"
            ],
            "profile" => [
                "text" => "My Profile",
                "link" => "/student/profile",
                "icon" => "fas fa-user"
            ],

            "academic" => [
                "text" => "Academic",
                "icon" => "fas fa-book",
                "group" => true,
                "items" => [
                    ["text" => "My Courses", "url" => "/student/courses"],
                    ["text" => "My Timetable", "url" => "/student/timetable"],
                    ["text" => "My Results", "url" => "/student/results"],
                    ["text" => "Clearance Request", "url" => "/student/clearance"],
                    ["text" => "My Transcript", "url" => "/student/transcript"]
                ]
            ],

            "evaluation" => [
                "text" => "Evaluation",
                "icon" => "fas fa-ruler-combined",
                "group" => true,
                "items" => [
                    ["text" => "Course Evaluation", "url" => "/student/evaluation/courses"],
                    ["text" => "Lecturer Evaluation", "url" => "/student/evaluation/lecturer"],
                ]
            ],

            "fees" => [
                "text" => "Fees & Payments",
                "icon" => "fas fa-wallet",
                "group" => true,
                "items" => [
                    ["text" => "Fee Details", "url" => "/student/fees"],
                    ["text" => "Payment History", "url" => "/student/fees/history"],
                    ["text" => "My Allowances", "url" => "/student/fees/allowance"]
                ]
            ],

            "attendance" => [
                "text" => "Attendance",
                "icon" => "fas fa-calendar-check",
                "link" => "/student/attendance"
            ],

            "medical" => [
                "text" => "Medical Info",
                "icon" => "fas fa-notes-medical",
                "link" => "/student/medical"
            ],

            "discipline" => [
                "text" => "Disciplinary Records",
                "icon" => "fas fa-exclamation-triangle",
                "link" => "/student/discipline"
            ],

            "alerts" => [
                "text" => "Job Alerts",
                "icon" => "fas fa-bell",
                "link" => "/student/job-alerts"
            ],
        ];
    }