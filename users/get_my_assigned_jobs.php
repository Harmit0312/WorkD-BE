<?php
require_once "../cors.php";
require_once "../config/db.php";
// session_start();

// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];

$sql = "
SELECT 
    j.id,
    j.title,
    j.description,
    j.budget,
    j.deadline,
    j.status,
    u.name AS client_name
FROM applications a
INNER JOIN jobs j ON a.job_id = j.id
INNER JOIN users u ON j.client_id = u.id
WHERE a.freelancer_id = ?
AND a.status = 'accepted'
AND j.status != 'deleted'
AND freelancer_deleted = 0
ORDER BY j.id DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $freelancer_id);
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
