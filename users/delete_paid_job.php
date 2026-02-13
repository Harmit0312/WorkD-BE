<?php
// session_start();
require_once "../config/db.php";

/* ===== CORS & Headers ===== */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== GET JOB ID ===== */
$client_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$job_id = isset($data['job_id']) ? (int)$data['job_id'] : 0;

if ($job_id <= 0) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Job ID"
    ]);
    exit;
}

/* ===== CHECK JOB OWNERSHIP ===== */
$check = $conn->prepare("
    SELECT id, status 
    FROM jobs 
    WHERE id = ? AND client_id = ?
");
$check->bind_param("ii", $job_id, $client_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "You cannot delete this job"
    ]);
    exit;
}

$job = $result->fetch_assoc();

/* ===== ONLY ALLOW CLIENT TO DELETE PAID JOBS ===== */
if (strtolower($job['status']) !== 'paid') {
    echo json_encode([
        "status" => false,
        "message" => "Only paid jobs can be removed from your view"
    ]);
    exit;
}

/* ===== SOFT DELETE FOR CLIENT ONLY =====
   We'll use a new column `client_deleted` (TINYINT 0/1) 
   Make sure to add this column in your jobs table:
   ALTER TABLE jobs ADD COLUMN client_deleted TINYINT(1) DEFAULT 0;
*/
$delete = $conn->prepare("
    UPDATE jobs
    SET client_deleted = 1
    WHERE id = ? AND client_id = ?
");
$delete->bind_param("ii", $job_id, $client_id);

if ($delete->execute() && $delete->affected_rows > 0) {
    echo json_encode([
        "status" => true,
        "message" => "Paid job removed"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to remove job from your view"
    ]);
}

$delete->close();
$check->close();
$conn->close();
