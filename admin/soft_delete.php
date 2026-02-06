<?php
require_once "../config/db.php";

/* ===== CORS ===== */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===== INPUT ===== */
$data = json_decode(file_get_contents("php://input"), true);
$ids = $data['ids'] ?? [];

if (empty($ids)) {
    echo json_encode([
        "status" => false,
        "message" => "User IDs required"
    ]);
    exit;
}

/* ===== SOFT DELETE ===== */
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$sql = "UPDATE users SET deleted_at = CURDATE() WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$ids);
$stmt->execute();

echo json_encode([
    "status" => true,
    "message" => "Users soft deleted successfully"
]);
