<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$client_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$job_id = $data['job_id'] ?? null;

if (!$job_id) {
    echo json_encode(["status" => false, "message" => "Invalid Job ID"]);
    exit;
}

/* ✅ Check ownership */
$check = $conn->prepare("
SELECT id 
FROM jobs 
WHERE id = ? 
AND client_id = ?
");

$check->bind_param("ii", $job_id, $client_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "You cannot delete this job"]);
    exit;
}

/* ✅ Soft delete */
$delete = $conn->prepare("
UPDATE jobs 
SET status = 'deleted' 
WHERE id = ?
");

$delete->bind_param("i", $job_id);

if ($delete->execute() && $delete->affected_rows > 0) {
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
