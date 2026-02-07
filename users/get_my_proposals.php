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

$freelancer_id = $_SESSION['user_id'];

/* ===== QUERY ===== */
$sql = "
SELECT 
    a.id,
    a.proposal AS message,
    a.status,
    j.title AS job_title
FROM applications a
JOIN jobs j ON j.id = a.job_id
WHERE a.freelancer_id = ?
ORDER BY a.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();

$result = $stmt->get_result();
$proposals = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "status" => true,
    "proposals" => $proposals
]);
