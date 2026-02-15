<?php
require_once "../cors.php";
require_once "../config/db.php";

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

/* ===== GET CLIENT INFO ===== */
$getClient = $conn->prepare("SELECT name FROM users WHERE id = ?");
$getClient->bind_param("i", $client_id);
$getClient->execute();
$clientResult = $getClient->get_result();
$client = $clientResult->fetch_assoc();

/* ===== TOTAL SPENDING ===== */
$getEarnings = $conn->prepare("
    SELECT SUM(amount) AS total_spent
    FROM orders
    WHERE client_id = ? AND status = 'paid'
");
$getEarnings->bind_param("i", $client_id);
$getEarnings->execute();
$earningsResult = $getEarnings->get_result();
$earningsRow = $earningsResult->fetch_assoc();

$total_spent = $earningsRow['total_spent'] ?? 0;

/* ===== FREELANCERS WORKED WITH ===== */
$getFreelancers = $conn->prepare("
    SELECT DISTINCT u.id, u.name, u.email, u.skills
    FROM orders o
    JOIN users u ON o.freelancer_id = u.id
    WHERE o.client_id = ? AND o.status = 'paid'
");
$getFreelancers->bind_param("i", $client_id);
$getFreelancers->execute();
$freelancersResult = $getFreelancers->get_result();

$freelancers = [];
while ($row = $freelancersResult->fetch_assoc()) {
    $freelancers[] = $row;
}

echo json_encode([
    "status" => true,
    "profile" => [
        "name" => $client['name'],
        "earnings" => (float)$total_spent,
        "freelancers" => $freelancers
    ]
]);
