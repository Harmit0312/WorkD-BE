<?php
require_once "../cors.php";
require_once "../config/db.php";
// session_start();

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['current_password']) || empty($data['new_password'])) {
    echo json_encode(["status" => false, "message" => "All fields required"]);
    exit;
}

/* Get current password */
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!password_verify($data['current_password'], $user['password'])) {
    echo json_encode(["status" => false, "message" => "Current password incorrect"]);
    exit;
}

/* Update password */
$newHashed = password_hash($data['new_password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $newHashed, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Password updated successfully"]);
} else {
    echo json_encode(["status" => false, "message" => "Update failed"]);
}
