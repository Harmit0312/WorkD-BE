<?php
require_once "../cors.php";
require_once "../config/db.php";

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$job_id = $data['job_id'] ?? null;
$client_id = $_SESSION['user_id'];

if (!$job_id) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Job ID"
    ]);
    exit;
}

/* ===== GET JOB ===== */
$getJob = $conn->prepare("
    SELECT id, budget 
    FROM jobs 
    WHERE id = ? 
      AND client_id = ? 
      AND status = 'completed'
    LIMIT 1
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
$amount = (float)$job['budget'];

/* ===== CHECK IF ALREADY PAID ===== */
$checkPayment = $conn->prepare("
    SELECT id 
    FROM orders 
    WHERE job_id = ? AND status = 'paid'
    LIMIT 1
");
$checkPayment->bind_param("i", $job_id);
$checkPayment->execute();

if ($checkPayment->get_result()->num_rows > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Payment already completed"
    ]);
    exit;
}

/* ===== GET ACCEPTED FREELANCER ===== */
$getFreelancer = $conn->prepare("
    SELECT freelancer_id 
    FROM applications 
    WHERE job_id = ? AND status = 'accepted'
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

$freelancer_id = $freelancerResult->fetch_assoc()['freelancer_id'];

/* ===== GET COMMISSION ===== */
$commissionRes = $conn->query("
    SELECT commission_percentage 
    FROM admin_settings 
    LIMIT 1
");
$commission_percentage = (float)$commissionRes->fetch_assoc()['commission_percentage'];

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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')
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

    /* ✅ UPDATE JOB STATUS TO PAID */
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