<?php
require_once "../cors.php";
require_once "../config/db.php";
// session_start();

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];

/* Get Freelancer Info */
$stmt = $conn->prepare("SELECT name, skills FROM users WHERE id = ? AND role = 'freelancer'");
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => false, "message" => "Freelancer not found"]);
    exit;
}

/* Get Total Earnings (Only Paid Orders) */
$stmt = $conn->prepare("
    SELECT IFNULL(SUM(freelancer_amount),0) as total
    FROM orders
    WHERE freelancer_id = ?
    AND status = 'paid'
");
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$earnings = $stmt->get_result()->fetch_assoc()['total'];

/* Get Clients Worked With (Paid Orders Only) */
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.name, u.email
    FROM orders o
    JOIN users u ON o.client_id = u.id
    WHERE o.freelancer_id = ?
    AND o.status = 'paid'
");
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$clientsResult = $stmt->get_result();

$clients = [];
while ($row = $clientsResult->fetch_assoc()) {
    $clients[] = $row;
}

echo json_encode([
    "status" => true,
    "profile" => [
        "name" => $user['name'],
        "skills" => $user['skills'],
        "earnings" => (int)$earnings,
        "clients" => $clients
    ]
]);
