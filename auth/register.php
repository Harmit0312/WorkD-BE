<?php
// session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "No JSON received"]);
    exit;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? '';

if (!$name || !$email || !$password || !$role) {
    http_response_code(400);
    echo json_encode(["error" => "All fields are required"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Email already exists"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)"
);
$stmt->bind_param("ssss", $name, $email, $hashed, $role);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Registration successful"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Registration failed"
    ]);
}
