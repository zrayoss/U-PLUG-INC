<?php

date_default_timezone_set('Asia/Manila');

$db_name = 'db_uplug';
$host = 'localhost';
$username = 'root';
$password = '';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $username, $password, $db_name);
} catch (mysqli_sql_exception $e){
    die("Connection failed. " . $e->getMessage() . " " . $e->getCode());
}

return $conn;