<?php

require_once "../cors.php";
require_once "../config/db.php";

header("Content-Type: application/json");

// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];

$stats = [
    "activeContracts" => 0,
    "completedProjects" => 0,
    "earnings" => 0,
    // "skillsEndorsements" => 0
];

/* ✅ Active Contracts */
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT j.id) as total
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.freelancer_id = ?
    AND a.status = 'accepted'
    AND j.status IN ('assigned', 'completed')
    AND j.freelancer_deleted = 0
    AND j.client_deleted = 0
    AND j.admin_deleted = 0
");
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats["activeContracts"] = (int)$row['total'];

/* ✅ Completed Projects */
$result = $conn->query("
    SELECT COUNT(*) as total
    FROM jobs j
    JOIN orders o ON o.job_id = j.id
    WHERE o.freelancer_id = $freelancer_id
    AND j.status IN ('completed', 'paid', 'deleted')
");
$row = $result->fetch_assoc();
$stats["completedProjects"] = (int)$row['total'];

/* ✅ Earnings (Sum of freelancer_amount) */
$result = $conn->query("
    SELECT SUM(freelancer_amount) as total 
    FROM orders 
    WHERE freelancer_id = $freelancer_id 
    AND status = 'paid'
");
$row = $result->fetch_assoc();
$stats["earnings"] = $row['total'] ? (float)$row['total'] : 0;

/* ✅ Skills Endorsements (using reviews count) */
// $result = $conn->query("
//     SELECT COUNT(*) as total
//     FROM reviews r
//     JOIN orders o ON r.order_id = o.id
//     WHERE o.freelancer_id = $freelancer_id
// ");
// $row = $result->fetch_assoc();
// $stats["skillsEndorsements"] = (int)$row['total'];

/* Get Freelancer Name */
$userResult = $conn->query("SELECT name FROM users WHERE id = $freelancer_id");
$userRow = $userResult->fetch_assoc();

echo json_encode([
    "status" => true,
    "user_name" => $userRow['name'],
    "stats" => $stats
]);
