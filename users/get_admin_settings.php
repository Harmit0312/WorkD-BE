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

$admin_id = $_SESSION['user_id'];

/* Get admin name */
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

/* Get commission */
$commissionQuery = $conn->query("SELECT commission_percentage FROM admin_settings LIMIT 1");
$commission = $commissionQuery->fetch_assoc();

echo json_encode([
    "status" => true,
    "name" => $admin['name'] ?? '',
    "commission" => $commission['commission_percentage'] ?? 0
]);
