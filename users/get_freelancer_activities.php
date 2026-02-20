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

$activities = [];

/* Completed projects */
$result = $conn->query("
    SELECT j.title, o.id, o.status
    FROM orders o
    JOIN jobs j ON o.job_id = j.id
    WHERE o.freelancer_id = $freelancer_id
    ORDER BY o.id DESC
    LIMIT 5
");

while ($row = $result->fetch_assoc()) {

    $action = "";

    if ($row['status'] == 'completed') {
        $action = "Completed project: " . $row['title'];
    } elseif ($row['status'] == 'assigned') {
        $action = "Started working on: " . $row['title'];
    } elseif ($row['status'] == 'paid') {
        $action = "Received payment for: " . $row['title'];
    } else {
        continue;
    }

    $activities[] = [
        "time" => "Recently",
        "action" => $action
    ];
}

echo json_encode([
    "status" => true,
    "activities" => $activities
]);
