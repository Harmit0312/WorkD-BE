<?php
require_once "../config/db.php";
// session_start();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$client_id = $_SESSION['user_id'];

$sql = "
SELECT 
    j.id,
    j.title,
    j.description,
    j.budget,
    j.deadline,
    j.status,
    u.name AS assigned_freelancer_name,
    u.id AS freelancer_id
FROM jobs j
LEFT JOIN applications a 
    ON j.id = a.job_id 
    AND a.status = 'accepted'
LEFT JOIN users u 
    ON a.freelancer_id = u.id
WHERE j.client_id = ?
AND j.status != 'deleted'
AND client_deleted = 0
ORDER BY j.id DESC
";

$stmt = $conn->prepare($sql);
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
