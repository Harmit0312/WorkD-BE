<?php
require_once "../config/db.php";

/* CORS */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

/* Read JSON */
$data = json_decode(file_get_contents("php://input"), true);

$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$budget = $data['budget'] ?? '';
$deadline = $data['deadline'] ?? '';

if (!$title || !$description || !$budget || !$deadline) {
    echo json_encode([
        "status" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

/* IMPORTANT: client_id from session */
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$client_id = $_SESSION['user_id'];
$status = "open";

$stmt = $conn->prepare(
    "INSERT INTO jobs (client_id, title, description, budget, deadline, status)
     VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "ississ",
    $client_id,
    $title,
    $description,
    $budget,
    $deadline,
    $status
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Job posted successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}
