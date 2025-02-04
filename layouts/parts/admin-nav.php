<?php 
    /* 
        "" => [
                "text" => "",
                "link" => "",
                "icon" => ""
            ]
    */
    if((isset($_SESSION["admin_register"]) && $_SESSION["admin_register"]) || (isset($setup_page) && $setup_page)){
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
            ],
            "activate" => [
                "text" => "Activate System",
                "link" => "/admin-setup/activate",
                "icon" => "fas ".(isset($_SESSION["admin_register"]) && $_SESSION["admin_register"] ? "fas fa-toggle-off" : "fas fa-toggle-on")
            ]
        ];
    }else{
        return [
            "dashboard" => [
                "text" => "Dashboard",
                "link" => "/admin/dashboard",
                "icon" => "fas fa-compass"
            ]
        ];
    }