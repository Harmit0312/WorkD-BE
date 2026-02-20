<?php
require_once "../cors.php";
require_once "../config/db.php";

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$commission = intval($data['commission']);

if ($commission < 0 || $commission > 100) {
    echo json_encode(["status" => false, "message" => "Invalid commission"]);
    exit;
}

/* Ensure row exists */
$check = $conn->query("SELECT id FROM admin_settings LIMIT 1");

if ($check->num_rows === 0) {
    $conn->query("INSERT INTO admin_settings (commission_percentage) VALUES ($commission)");
} else {
    $conn->query("UPDATE admin_settings SET commission_percentage = $commission WHERE id = 1");
}

echo json_encode(["status" => true, "message" => "Commission updated successfully"]);
