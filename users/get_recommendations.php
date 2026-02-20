<?php
require_once "../cors.php";
require_once "../config/db.php";

// header("Content-Type: application/json");

// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];

$query = "
SELECT j.id, j.title, j.budget, j.deadline, u.name as client
FROM jobs j
JOIN users u ON j.client_id = u.id
WHERE j.status = 'open'
AND j.id NOT IN (
    SELECT job_id FROM applications WHERE freelancer_id = $freelancer_id
)
ORDER BY j.deadline ASC
LIMIT 6
";

$result = $conn->query($query);

$recommendations = [];

while ($row = $result->fetch_assoc()) {
    $recommendations[] = [
        "id" => $row['id'],
        "title" => $row['title'],
        "client" => $row['client'],
        "budget" => "₹" . number_format($row['budget']),
        "deadline" => date("d M Y", strtotime($row['deadline']))
    ];
}

echo json_encode([
    "status" => true,
    "recommendations" => $recommendations
]);
