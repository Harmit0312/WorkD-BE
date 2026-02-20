<?php
require_once "../cors.php";
// session_start();
require_once "../config/db.php";

/* ===== CORS ===== */
// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Content-Type: application/json");

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    http_response_code(401);
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== READ INPUT ===== */
$data = json_decode(file_get_contents("php://input"), true);

$title       = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$budget      = $data['budget'] ?? '';
$deadline    = $data['deadline'] ?? '';

if (!$title || !$description || !$budget || !$deadline) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

$client_id = $_SESSION['user_id'];
$status = "open";

/* ===== INSERT JOB ===== */
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

    /* ===== UPDATE USER ACTIVITY ===== */
    $activity = $conn->prepare(
        "UPDATE user_activity 
         SET jobs_posted = jobs_posted + 1 
         WHERE user_id = ?"
    );
    $activity->bind_param("i", $client_id);
    $activity->execute();

    echo json_encode([
        "success" => true,
        "message" => "Job posted successfully"
    ]);

} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}
