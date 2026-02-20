<?php
require_once "../cors.php";
// session_start();
require_once "../config/db.php";

/* ===== CORS ===== */
// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== GET JOB ID ===== */
$input = json_decode(file_get_contents("php://input"), true);
$jobId = isset($input['job_id']) ? (int)$input['job_id'] : 0;

if ($jobId <= 0) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid job ID"
    ]);
    exit;
}

/* ===== CHANGE STATUS TO 'deleted' FOR OPEN JOBS OWNED BY CLIENT ===== */
$stmt = $conn->prepare("
    UPDATE jobs
    SET status = 'deleted'
    WHERE id = ? AND client_id = ? AND status = 'open'
");

$stmt->bind_param("ii", $jobId, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode([
        "status" => true,
        "message" => "Open job deleted successfully (status changed to deleted)"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Job not found, not open, or you do not have permission"
    ]);
}

$stmt->close();
$conn->close();
