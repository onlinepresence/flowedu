<?php 
    /* 
        "" => [
                "text" => "",
                "link" => "",
                "icon" => ""
            ]
    */
    if($_SESSION["admin_register"]){
        return [
            "personal" => [
                "text" => "Personal Information",
                "link" => "/admin-setup/personal",
                "icon" => "fas fa-address-card"
            ],
            "school" => [
                "text" => "Setup School",
                "link" => "/admin-setup/school",
                "icon" => "fas fa-school"
            ],
            "programs" => [
                "text" => "Setup Programs / Courses",
                "link" => "/admin-setup/programs",
                "icon" => "fas fa-book"
            ],
            "halls" => [
                "text" => "Setup Halls",
                "link" => "/admin-setup/halls",
                "icon" => "fas fa-house-chimney-user"
            ]
        ];
    }else{
        return [
            
        ];
    }