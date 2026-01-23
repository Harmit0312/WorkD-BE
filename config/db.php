<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "WorkD";

session_start();
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

header("Content-Type: application/json");
?>
