<?php 
    // redirect to generate env if no env has been created
    if(empty($_ENV)){
        require_once "load_env.php";

        if(empty($_ENV))
            header("location: /env-generator");
    }

    $serverName = $_SERVER['SERVER_NAME'] ?? env("APP_ENV");
    $serverDown = env("SERVER_DOWN") == "true";
    $last_exception = null;

    $sqlServer = array();

    //determine development server and live server to determine how error codes are shown
    $developmentServer = null;

    if($serverDown === false){
        // database connection
        $sqlServer =   [
            "host" => env('DB_HOST'),
            "hostpassword" => env("DB_PASSWORD"),
            "hostname" => env("DB_USERNAME"),
            "db" => env("DB_NAME")
        ];

        $developmentServer = env("APP_ENV") == "local";

        // mail server configuration
        $mailserver_email = env("MAIL_USERNAME");
        $mailserver_password = env("MAIL_PASSWORD");
        $mailserver = env("MAIL_HOST");

        $phone_prefixes = [
            "027","057","026","056","024",
            "025","053","054","055","059",
            "020","050","023"
        ];

        $provider_prefixes = [
            "airteltigo" => ["027","057","026","056"],
            "mtn" => ["024","025","053","054","055","059"],
            "vodafone" => ["020","050"],
            "glo" => ["023"]
        ];
    }
?>