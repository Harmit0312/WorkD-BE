<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

session_start();
session_unset();
session_destroy();

echo json_encode([
    "status" => true,
    "message" => "Logged out successfully"
]);
