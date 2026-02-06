<?php
require_once "../config/db.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "User ID required"]);
    exit;
}

$id = (int)$_GET['id'];

$sql = "
    SELECT 
        u.id, u.name, u.email, u.role, u.join_date,
        IFNULL(ua.jobs_posted, 0) AS jobs_posted,
        IFNULL(ua.proposals_sent, 0) AS proposals_sent,
        IFNULL(ua.completed_orders, 0) AS completed_orders
    FROM users u
    LEFT JOIN user_activity ua ON ua.user_id = u.id
    WHERE u.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => false, "message" => "User not found"]);
    exit;
}

echo json_encode([
    "status" => true,
    "user" => $user
]);
