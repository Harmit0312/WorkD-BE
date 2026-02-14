<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['job_id'])) {
    echo json_encode(["status" => false, "message" => "Job ID required"]);
    exit;
}

$job_id = intval($data['job_id']);

/* ✅ Verify job belongs to this freelancer and is assigned */
$check_sql = "
SELECT j.id
FROM jobs j
JOIN applications a ON j.id = a.job_id
WHERE j.id = ?
AND a.freelancer_id = ?
AND a.status = 'accepted'
AND j.status = 'assigned'
";

$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $job_id, $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "Job not found or already completed"
    ]);
    exit;
}

/* ✅ Update job to completed */
$update_sql = "UPDATE jobs SET status = 'completed' WHERE id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $job_id);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => false,
        "message" => "Failed to update job"
    ]);
    exit;
}

/* ✅ Update user_activity table */

// Check if record exists
$check_activity = $conn->prepare("SELECT user_id FROM user_activity WHERE user_id = ?");
$check_activity->bind_param("i", $freelancer_id);
$check_activity->execute();
$activity_result = $check_activity->get_result();

if ($activity_result->num_rows > 0) {
    // Update existing row
    $update_activity = $conn->prepare("
        UPDATE user_activity 
        SET completed_orders = completed_orders + 1 
        WHERE user_id = ?
    ");
    $update_activity->bind_param("i", $freelancer_id);
    $update_activity->execute();
} else {
    // Insert new row
    $insert_activity = $conn->prepare("
        INSERT INTO user_activity (user_id, completed_orders) 
        VALUES (?, 1)
    ");
    $insert_activity->bind_param("i", $freelancer_id);
    $insert_activity->execute();
}

echo json_encode([
    "status" => true,
    "message" => "Job marked as completed successfully"
]);
