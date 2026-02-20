<?php
require_once "../cors.php";
require_once "../config/db.php";
// session_start();

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['name'])) {
    echo json_encode(["status" => false, "message" => "Name required"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
$stmt->bind_param("si", $data['name'], $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Name updated successfully"]);
} else {
    echo json_encode(["status" => false, "message" => "Update failed"]);
}
