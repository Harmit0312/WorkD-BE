<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');

if (!$name) {
    echo json_encode(["status" => false, "message" => "Name required"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ? AND role = 'admin'");
$stmt->bind_param("si", $name, $_SESSION['user_id']);
$stmt->execute();

echo json_encode(["status" => true, "message" => "Name updated successfully"]);
