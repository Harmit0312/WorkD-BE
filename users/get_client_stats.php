<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

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

/* ===== CLIENT NAME ===== */
$getClient = $conn->prepare("SELECT name FROM users WHERE id = ?");
$getClient->bind_param("i", $client_id);
$getClient->execute();
$clientResult = $getClient->get_result();
$client = $clientResult->fetch_assoc();
$client_name = $client['name'] ?? "Client";

/* ===== JOBS POSTED (NOT DELETED) ===== */
$getJobsPosted = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM jobs 
    WHERE client_id = ? AND client_deleted = 0
");
$getJobsPosted->bind_param("i", $client_id);
$getJobsPosted->execute();
$jobsPosted = $getJobsPosted->get_result()->fetch_assoc()['total'] ?? 0;

/* ===== ACTIVE JOBS ===== */
/* assigned + not deleted */
$getActiveJobs = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM jobs
    WHERE client_id = ?
    AND status = 'assigned'
    AND client_deleted = 0
");
$getActiveJobs->bind_param("i", $client_id);
$getActiveJobs->execute();
$activeJobs = $getActiveJobs->get_result()->fetch_assoc()['total'] ?? 0;

/* ===== COMPLETED JOBS (SHOW HISTORY) ===== */
$getCompletedJobs = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM jobs
    WHERE client_id = ?
    AND status = 'completed'
");
$getCompletedJobs->bind_param("i", $client_id);
$getCompletedJobs->execute();
$completedJobs = $getCompletedJobs->get_result()->fetch_assoc()['total'] ?? 0;

/* ===== TOTAL SPENT (PAID ORDERS ONLY) ===== */
$getSpent = $conn->prepare("
    SELECT SUM(amount) AS total_spent
    FROM orders
    WHERE client_id = ?
    AND status = 'paid'
");
$getSpent->bind_param("i", $client_id);
$getSpent->execute();
$totalSpent = $getSpent->get_result()->fetch_assoc()['total_spent'] ?? 0;

echo json_encode([
    "status" => true,
    "client_name" => $client_name,
    "stats" => [
        "jobsPosted" => (int)$jobsPosted,
        "activeJobs" => (int)$activeJobs,
        "completedJobs" => (int)$completedJobs,
        "totalSpent" => (float)$totalSpent
    ]
]);
