<?php
require_once "../config/db.php";
// session_start();

/* ===== CORS ===== */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== INPUT ===== */
$data = json_decode(file_get_contents("php://input"), true);

$job_id      = $data['job_id'] ?? '';
$title       = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$budget      = trim($data['budget'] ?? '');

if (!$job_id || !$title || !$description || !$budget) {
    echo json_encode([
        "status" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

/* ===== CHECK JOB OWNERSHIP ===== */
$checkSql = "SELECT id FROM jobs WHERE id = ? AND client_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $job_id, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "You are not allowed to update this job"
    ]);
    exit;
}

/* ===== UPDATE JOB ===== */
$updateSql = "
    UPDATE jobs 
    SET title = ?, description = ?, budget = ?
    WHERE id = ?
";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("ssii", $title, $description, $budget, $job_id);

if ($updateStmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Job updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to update job"
    ]);
}
