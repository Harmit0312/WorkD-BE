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

$activities = [];

/* ===== LAST 5 JOB POSTS ===== */
$getJobs = $conn->prepare("
    SELECT title, status
    FROM jobs
    WHERE client_id = ?
    ORDER BY id DESC
    LIMIT 5
");
$getJobs->bind_param("i", $client_id);
$getJobs->execute();
$resultJobs = $getJobs->get_result();

while ($row = $resultJobs->fetch_assoc()) {

    if ($row['status'] === 'assigned') {
        $activities[] = "Job '{$row['title']}' has been assigned to a freelancer.";
    }
    elseif ($row['status'] === 'completed') {
        $activities[] = "Job '{$row['title']}' has been completed.";
    }
    else {
        $activities[] = "You posted a new job '{$row['title']}'.";
    }
}

/* ===== LAST 5 PAYMENTS ===== */
$getPayments = $conn->prepare("
    SELECT j.title, o.amount
    FROM orders o
    JOIN jobs j ON o.job_id = j.id
    WHERE o.client_id = ?
    AND o.status = 'paid'
    ORDER BY o.id DESC
    LIMIT 5
");
$getPayments->bind_param("i", $client_id);
$getPayments->execute();
$resultPayments = $getPayments->get_result();

while ($row = $resultPayments->fetch_assoc()) {
    $activities[] = "You made a payment of ₹{$row['amount']} for '{$row['title']}'.";
}

/* LIMIT TOTAL TO 10 */
$activities = array_slice($activities, 0, 10);

echo json_encode([
    "status" => true,
    "activities" => $activities
]);
