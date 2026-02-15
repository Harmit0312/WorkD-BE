<?php
require_once "../cors.php";
require_once "../config/db.php";

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

if (!$current_password || !$new_password) {
    echo json_encode(["status" => false, "message" => "All fields required"]);
    exit;
}

$client_id = $_SESSION['user_id'];

$getUser = $conn->prepare("SELECT password FROM users WHERE id = ?");
$getUser->bind_param("i", $client_id);
$getUser->execute();
$result = $getUser->get_result();
$user = $result->fetch_assoc();

if (!password_verify($current_password, $user['password'])) {
    echo json_encode(["status" => false, "message" => "Current password incorrect"]);
    exit;
}

$new_hashed = password_hash($new_password, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->bind_param("si", $new_hashed, $client_id);

if ($update->execute()) {
    echo json_encode(["status" => true, "message" => "Password updated successfully"]);
} else {
    echo json_encode(["status" => false, "message" => "Update failed"]);
}
