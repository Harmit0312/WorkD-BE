<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
$data = json_decode(file_get_contents("php://input"), true);

$job_id = $data['job_id'] ?? null;

if (!$job_id) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid job ID"
    ]);
    exit;
}

/* ===== CHECK JOB ===== */
$getJob = $conn->prepare("
    SELECT id, budget
    FROM jobs 
    WHERE id = ? 
    AND client_id = ?
    AND status = 'completed'
");

$getJob->bind_param("ii", $job_id, $client_id);
$getJob->execute();
$jobResult = $getJob->get_result();

if ($jobResult->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "Job not eligible for payment"
    ]);
    exit;
}

$job = $jobResult->fetch_assoc();
$amount = $job['budget'];

/* ===== CHECK IF ALREADY PAID ===== */
$checkPayment = $conn->prepare("
    SELECT id FROM orders 
    WHERE job_id = ? AND status = 'paid'
    LIMIT 1
");
$checkPayment->bind_param("i", $job_id);
$checkPayment->execute();
$existing = $checkPayment->get_result();

if ($existing->num_rows > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Payment already completed for this job"
    ]);
    exit;
}

/* ===== GET ACCEPTED FREELANCER ===== */
$getFreelancer = $conn->prepare("
    SELECT freelancer_id 
    FROM applications
    WHERE job_id = ?
    AND status = 'accepted'
    LIMIT 1
");

$getFreelancer->bind_param("i", $job_id);
$getFreelancer->execute();
$freelancerResult = $getFreelancer->get_result();

if ($freelancerResult->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "No accepted freelancer found"
    ]);
    exit;
}

$freelancer = $freelancerResult->fetch_assoc();
$freelancer_id = $freelancer['freelancer_id'];

/* ===== CALCULATE COMMISSION ===== */
$commission_percentage = 10;
$commission_amount = ($amount * $commission_percentage) / 100;
$freelancer_amount = $amount - $commission_amount;

/* ===== TRANSACTION ===== */
$conn->begin_transaction();

try {

    /* INSERT ORDER */
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
        $freelancer_id,
        $amount,
        $commission_percentage,
        $commission_amount,
        $freelancer_amount
    );

    $insertOrder->execute();

    /* UPDATE JOB STATUS */
    $updateJob = $conn->prepare("
        UPDATE jobs 
        SET status = 'paid'
        WHERE id = ?
    ");
    $updateJob->bind_param("i", $job_id);
    $updateJob->execute();

    $conn->commit();

    echo json_encode([
        "status" => true,
        "message" => "Payment successful"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => false,
        "message" => "Payment failed"
    ]);
}
