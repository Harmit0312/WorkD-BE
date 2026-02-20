<?php
require_once "../cors.php";
// session_start();
require_once "../config/db.php";

/* ===== CORS & Headers ===== */
// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== GET JOB ID ===== */
$data = json_decode(file_get_contents("php://input"), true);
$job_id = isset($data['job_id']) ? (int)$data['job_id'] : 0;

if ($job_id <= 0) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Job ID"
    ]);
    exit;
}

/* ===== CHECK JOB IS PAID ===== */
$check = $conn->prepare("
    SELECT id
    FROM jobs
    WHERE id = ? 
      AND status = 'paid'
    LIMIT 1
");
$check->bind_param("i", $job_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "Job not found or not paid"
    ]);
    exit;
}

/* ===== SOFT DELETE FOR ADMIN ONLY ===== */
$delete = $conn->prepare("
    UPDATE jobs
    SET admin_deleted = 1
    WHERE id = ? 
      AND admin_deleted = 0
    LIMIT 1
");
$delete->bind_param("i", $job_id);
$delete->execute();

if ($delete->affected_rows > 0) {
    echo json_encode([
        "status" => true,
        "message" => "Job removed successfully"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to remove job"
    ]);
}

$delete->close();
$check->close();
$conn->close();
