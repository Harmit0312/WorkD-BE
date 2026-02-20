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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== GET JOB ID ===== */
$freelancer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$job_id = isset($data['job_id']) ? (int)$data['job_id'] : 0;

if ($job_id <= 0) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Job ID"
    ]);
    exit;
}

/* ===== CHECK JOB BELONGS TO FREELANCER AND IS PAID ===== */
$check = $conn->prepare("
    SELECT j.id
    FROM applications a
    INNER JOIN jobs j ON a.job_id = j.id
    WHERE a.freelancer_id = ?
      AND a.status = 'accepted'
      AND j.id = ?
      AND j.status = 'paid'
    LIMIT 1
");
$check->bind_param("ii", $freelancer_id, $job_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "You cannot delete this job"
    ]);
    exit;
}

/* ===== SOFT DELETE FOR FREELANCER =====
   This must set freelancer_deleted = 1
   Make sure the column exists: 
   ALTER TABLE jobs ADD COLUMN freelancer_deleted TINYINT(1) DEFAULT 0;
*/
$delete = $conn->prepare("
    UPDATE jobs
    SET freelancer_deleted = 1
    WHERE id = ? 
      AND freelancer_deleted = 0
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
        "message" => "Failed to remove job (it may already be deleted)"
    ]);
}

$delete->close();
$check->close();
$conn->close();
