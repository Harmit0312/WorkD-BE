<?php
require_once "../cors.php";
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

/* ===== AUTH ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$proposal_id = $data['proposal_id'] ?? null;

if (!$proposal_id) {
    echo json_encode(["status" => false, "message" => "Invalid proposal ID"]);
    exit;
}

/* ===== DELETE ===== */
$sql = "
DELETE FROM applications
WHERE id = ? AND freelancer_id = ? AND status = 'pending'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => true, "message" => "Proposal withdrawn"]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Cannot withdraw this proposal"
    ]);
}
