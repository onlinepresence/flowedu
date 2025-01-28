<?php 
    $serverName = $_SERVER['SERVER_NAME'];
    $serverDown = false;

    $sqlServer = array();

    //determine development server and live server to determine how error codes are shown
    $developmentServer = null;

    if($serverDown === false){        
        switch($serverName){
            case "localhost":
            case "college-school.local":
            case "www.college-school.local":
                $sqlServer = [
                    "host" => "localhost",
                    "hostpassword" => "",
                    "hostname" => "root",
                    "db" => "student-system"
                ];

                $developmentServer = true;

                // mail server configuration
                $mailserver_email = "successinnovativehub@gmail.com";
                $mailserver_password = "wzap xjim dvpv bhfe";
                $mailserver = "smtp.gmail.com";

                break;
            case "college.shsdesk.com":
            case "www.college.shsdesk.com":
                $sqlServer = [
                    "host" => "localhost",
                    "hostpassword" => "Password@2020",
                    "hostname" => "shsdeskc_matrixme",
                    "db" => "shsdeskc_student_system"
                ];

                $developmentServer = false;

                break;

        }

        $phone_numbers = [
            "027","057","026","056","024",
            "025","053","054","055","059",
            "020","050","023"
        ];

        $service_providers = [
            "airteltigo" => ["027","057","026","056"],
            "mtn" => ["024","025","053","054","055","059"],
            "vodafone" => ["020","050"],
            "glo" => ["023"]
        ];
    }
?>