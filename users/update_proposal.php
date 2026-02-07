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

/* ===== AUTH ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$proposal_id = $data['proposal_id'] ?? null;
$message = trim($data['message'] ?? '');

if (!$proposal_id || $message === '') {
    echo json_encode(["status" => false, "message" => "Invalid data"]);
    exit;
}

/* ===== UPDATE ===== */
$sql = "
UPDATE applications
SET proposal = ?
WHERE id = ? AND freelancer_id = ? AND status = 'pending'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $message, $proposal_id, $_SESSION['user_id']);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => true, "message" => "Proposal updated"]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Cannot edit this proposal"
    ]);
}
