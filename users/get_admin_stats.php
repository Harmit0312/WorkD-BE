<?php
require_once "../cors.php";
require_once "../config/db.php";

// header("Content-Type: application/json");

// session_start();

/* 🔐 Only admin allowed */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$stats = [
    "totalClients" => 0,
    "totalFreelancers" => 0,
    "jobsPosted" => 0,
    "totalRevenue" => 0
];

/* ✅ Total Clients */
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'client'");
$row = $result->fetch_assoc();
$stats["totalClients"] = (int)$row['total'];

/* ✅ Total Freelancers */
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'freelancer'");
$row = $result->fetch_assoc();
$stats["totalFreelancers"] = (int)$row['total'];

/* ✅ Total Jobs Posted */
$result = $conn->query("SELECT COUNT(*) as total FROM jobs");
$row = $result->fetch_assoc();
$stats["jobsPosted"] = (int)$row['total'];

/* ✅ Total Revenue (Admin Commission from completed orders) */
$result = $conn->query("
    SELECT SUM(commission_amount) as revenue
    FROM orders
    WHERE status IN ('completed', 'paid')
");

$row = $result->fetch_assoc();
$stats["totalRevenue"] = $row['revenue'] ? (float)$row['revenue'] : 0;

/* Return response */
echo json_encode([
    "status" => true,
    "stats" => $stats
]);
