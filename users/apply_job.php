<?php
require_once "../config/db.php";

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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== INPUT ===== */
$data = json_decode(file_get_contents("php://input"), true);
$job_id = $data['job_id'] ?? null;
$proposal = trim($data["proposal"] ?? "");

if (!$job_id || $proposal === "") {
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "Job ID and proposal required"]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];

/* ===== PREVENT DUPLICATE APPLY ===== */
$check = $conn->prepare(
    "SELECT id FROM applications WHERE job_id = ? AND freelancer_id = ?"
);
$check->bind_param("ii", $job_id, $freelancer_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Already applied"
    ]);
    exit;
}

/* ===== INSERT APPLICATION ===== */
$stmt = $conn->prepare(
    "INSERT INTO applications (job_id, freelancer_id, proposal, status)
     VALUES (?, ?, ?, 'pending')"
);
$stmt->bind_param("iis", $job_id, $freelancer_id, $proposal);
if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Proposal sent successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => false, "message" => "Failed to send proposal"]);
}
