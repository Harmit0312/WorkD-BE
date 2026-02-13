<?php
require_once "../config/db.php";
session_start();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$client_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$job_id = $data['job_id'] ?? '';

if (!$job_id) {
    echo json_encode(["status" => false, "message" => "Invalid job ID"]);
    exit;
}

$check = $conn->prepare("
    SELECT id FROM jobs
    WHERE id = ?
    AND client_id = ?
    AND status = 'paid'
");
$check->bind_param("ii", $job_id, $client_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "Job cannot be deleted"]);
    exit;
}

$delete = $conn->prepare("DELETE FROM jobs WHERE id = ?");
$delete->bind_param("i", $job_id);
$delete->execute();

echo json_encode(["status" => true, "message" => "Job deleted"]);
