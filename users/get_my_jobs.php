<?php
require_once "../config/db.php";
// session_start();

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
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$client_id = $_SESSION['user_id'];

/* ===== FETCH JOBS ===== */
$stmt = $conn->prepare("
    SELECT id, title, description, budget, deadline
    FROM jobs
    WHERE client_id = ?
    AND status = 'open'
    ORDER BY id DESC
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

echo json_encode([
    "status" => true,
    "jobs" => $jobs
]);
