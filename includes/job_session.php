<?php
    include_once("appMemory.php");
    require_once("functions.php");

    $host = $sqlServer["host"];
    $hostname = $sqlServer["hostname"];
    $host_password = $sqlServer["hostpassword"];
    $dbname = $sqlServer["db"];

    $connect = new mysqli($host,$hostname,$host_password, $dbname);

    if($connect->connect_error){
        die("Connection failed -> Port 1...".$connect->connect_error);
    }

    $rootPath = dirname(__DIR__);

    date_default_timezone_set("Africa/Accra");
