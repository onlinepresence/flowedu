<?php
include_once("appMemory.php");

//stop execution if server is down
if($serverDown === true){
    header("location: /shutdown");
}

require_once("functions.php");

$host = $sqlServer["host"];
$hostname = $sqlServer["hostname"];
$host_password = $sqlServer["hostpassword"];
$dbname = $sqlServer["db"];

$connect = new mysqli($host,$hostname,$host_password, $dbname);

if($connect->connect_error){
    die("Connection failed -> Port 1...".$connect->connect_error);
}

//creating a default root path for finding php documents
$rootPath = $_SERVER["DOCUMENT_ROOT"];

//creating a default url for folder files
//grabbing protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off" || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

//adding the domain name
$domain_name = $_SERVER['HTTP_HOST'];

//$url = $protocol.$domain_name;
$url = $protocol.$domain_name;

//start a session
if(session_status() !== PHP_SESSION_ACTIVE)
    session_start();

date_default_timezone_set("Africa/Accra");

?>