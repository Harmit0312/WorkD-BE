<?php
require_once "../cors.php";
require_once "../config/db.php";

/* ===== CORS ===== */
// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: POST, OPTIONS");
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

/* ===== INPUT ===== */
$data = json_decode(file_get_contents("php://input"), true);

$job_id = $data['job_id'] ?? null;
$freelancer_id = $data['freelancer_id'] ?? null;

if (!$job_id || !$freelancer_id) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Job ID and Freelancer ID required"
    ]);
    exit;
}

$client_id = $_SESSION['user_id'];

/* ===== VERIFY JOB OWNERSHIP ===== */
$check = $conn->prepare(
    "SELECT id, status FROM jobs WHERE id = ? AND client_id = ?"
);
$check->bind_param("ii", $job_id, $client_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "Job not found or unauthorized"
    ]);
    exit;
}

$job = $res->fetch_assoc();
if ($job['status'] === 'assigned') {
    echo json_encode([
        "status" => false,
        "message" => "Job already assigned"
    ]);
    exit;
}

/* ===== ACCEPT SELECTED FREELANCER ===== */
$accept = $conn->prepare(
    "UPDATE applications 
     SET status = 'accepted' 
     WHERE job_id = ? AND freelancer_id = ?"
);
$accept->bind_param("ii", $job_id, $freelancer_id);
$accept->execute();

/* ===== REJECT OTHERS ===== */
$reject = $conn->prepare(
    "UPDATE applications 
     SET status = 'rejected' 
     WHERE job_id = ? AND freelancer_id != ?"
);
$reject->bind_param("ii", $job_id, $freelancer_id);
$reject->execute();

/* ===== UPDATE JOB STATUS ===== */
$updateJob = $conn->prepare(
    "UPDATE jobs SET status = 'assigned' WHERE id = ?"
);
$updateJob->bind_param("i", $job_id);
$updateJob->execute();

echo json_encode([
    "status" => true,
    "message" => "Job assigned successfully"
]);
