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
$name = trim($data['name'] ?? '');

if (empty($name)) {
    echo json_encode(["status" => false, "message" => "Name cannot be empty"]);
    exit;
}

$client_id = $_SESSION['user_id'];

$update = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
$update->bind_param("si", $name, $client_id);

if ($update->execute()) {
    echo json_encode(["status" => true, "message" => "Name updated successfully"]);
} else {
    echo json_encode(["status" => false, "message" => "Update failed"]);
}
