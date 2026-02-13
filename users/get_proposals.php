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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$client_id = $_SESSION['user_id'];

/* ===== FETCH CLIENT JOBS ===== */
$sql = "
    SELECT id, title, description, budget, status, deadline
    FROM jobs
    WHERE client_id = ?
    AND status != 'deleted'
    AND client_deleted = 0
    ORDER BY id DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];

while ($job = $result->fetch_assoc()) {

    $job_id = $job['id'];

    /* ===== FETCH PROPOSALS FOR JOB ===== */
    $p = $conn->prepare("
    SELECT 
        a.freelancer_id,
        u.name AS freelancer_name,
        u.email,
        u.experience,
        u.skills,
        a.proposal AS message,
        a.status,
        j.deadline
    FROM applications a
    JOIN users u ON u.id = a.freelancer_id
    JOIN jobs j ON j.id = a.job_id
    WHERE a.job_id = ?
    AND j.status != 'deleted'
    AND j.client_deleted = 0
    ORDER BY a.id DESC
");



    $p->bind_param("i", $job_id);
    $p->execute();

    $job['proposals'] = $p->get_result()->fetch_all(MYSQLI_ASSOC);

    $jobs[] = $job;
}

/* ===== RESPONSE ===== */
echo json_encode([
    "status" => true,
    "jobs" => $jobs
]);
