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

$current = $data['current_password'] ?? '';
$new = $data['new_password'] ?? '';

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($current, $user['password'])) {
    echo json_encode(["status" => false, "message" => "Current password incorrect"]);
    exit;
}

$newHash = password_hash($new, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->bind_param("si", $newHash, $_SESSION['user_id']);
$update->execute();

echo json_encode(["status" => true, "message" => "Password updated successfully"]);
