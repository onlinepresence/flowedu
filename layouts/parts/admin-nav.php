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
                "link" => "/admin/personal",
                "icon" => "fas fa-address-card"
            ]
        ];
    }else{
        return [
            
        ];
    }