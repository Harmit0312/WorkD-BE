<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$job_id = $data['job_id'] ?? null;

if (!$job_id) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Job ID"
    ]);
    exit;
}

/* ✅ Step 1: Check job belongs to freelancer AND is paid */
$check = $conn->prepare("
SELECT j.id
FROM applications a
INNER JOIN jobs j ON a.job_id = j.id
WHERE a.freelancer_id = ?
AND a.status = 'accepted'
AND j.id = ?
AND j.status = 'paid'
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

/* ✅ Step 2: Soft delete */
$delete = $conn->prepare("
UPDATE jobs 
SET status = 'deleted' 
WHERE id = ?
AND status = 'paid'
");

$delete->bind_param("i", $job_id);
$delete->execute();

if ($delete->affected_rows > 0) {
    echo json_encode([
        "status" => true,
        "message" => "Job deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Delete failed"
    ]);
}
