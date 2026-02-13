<?php
require_once "../config/db.php";
// session_start();

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

/* ===== Get Job Info ===== */
$getJob = $conn->prepare("
    SELECT * FROM jobs 
    WHERE id = ? 
    AND client_id = ?
    AND status = 'completed'
");
$getJob->bind_param("ii", $job_id, $client_id);
$getJob->execute();
$jobResult = $getJob->get_result();

if ($jobResult->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "Job not eligible for payment"]);
    exit;
}

$job = $jobResult->fetch_assoc();

$amount = $job['budget'];
$commission_percentage = 10; // example
$commission_amount = ($amount * $commission_percentage) / 100;
$freelancer_amount = $amount - $commission_amount;

/* ===== TRANSACTION ===== */
$conn->begin_transaction();

try {

    // 1️⃣ Insert into orders
    $insertOrder = $conn->prepare("
        INSERT INTO orders (
            job_id,
            client_id,
            freelancer_id,
            amount,
            commission_percentage,
            commission_amount,
            freelancer_amount,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')
    ");

    $insertOrder->bind_param(
        "iiiiddd",
        $job_id,
        $client_id,
        $job['freelancer_id'],
        $amount,
        $commission_percentage,
        $commission_amount,
        $freelancer_amount
    );

    $insertOrder->execute();

    // 2️⃣ Update job status
    $updateJob = $conn->prepare("
        UPDATE jobs 
        SET status = 'paid'
        WHERE id = ?
    ");
    $updateJob->bind_param("i", $job_id);
    $updateJob->execute();

    $conn->commit();

    echo json_encode(["status" => true, "message" => "Payment successful"]);
} catch (Exception $e) {

    $conn->rollback();
    echo json_encode(["status" => false, "message" => "Payment failed"]);
}
