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
            ]
        ];
    }else{
        return [
            "dashboard" => [
                "text" => "Dashboard",
                "link" => "/student/dashboard",
                "icon" => "fas fa-compass"
            ],
        ];
    }