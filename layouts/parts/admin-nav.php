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
            "faculty" => [
                "text" => "Setup Faculties",
                "link" => "/admin-setup/faculties",
                "icon" => "fas fa-building"
            ],
            "departments" => [
                "text" => "Setup Departments",
                "link" => "/admin-setup/departments",
                "icon" => "fas fa-briefcase"
            ],
            "programs" => [
                "text" => "Setup Programs",
                "link" => "/admin-setup/programs",
                "icon" => "fas fa-book"
            ],
            "halls" => [
                "text" => "Setup Halls",
                "link" => "/admin-setup/halls",
                "icon" => "fas fa-hotel"
            ]
        ];
    }else{
        return [
            
        ];
    }