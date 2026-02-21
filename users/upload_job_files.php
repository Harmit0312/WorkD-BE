<?php
require_once "../cors.php";
require_once "../config/db.php";

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

$freelancer_id = $_SESSION['user_id'];
$job_id = $_POST['job_id'] ?? null;

if (!$job_id || empty($_FILES['files'])) {
    echo json_encode(["status" => false, "message" => "Invalid data"]);
    exit;
}

/* ===== BLOCK SECOND UPLOAD ===== */
$check = $conn->prepare("
    SELECT id FROM job_files
    WHERE job_id = ? AND freelancer_id = ?
    LIMIT 1
");
$check->bind_param("ii", $job_id, $freelancer_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Files already uploaded for this job"
    ]);
    exit;
}

/* ===== UPLOAD DIRECTORY ===== */
$uploadDir = __DIR__ . "/../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===== HANDLE FILE ===== */
$name = $_FILES['files']['name'][0];
$tmp  = $_FILES['files']['tmp_name'][0];
$type = $_FILES['files']['type'][0];

$uniqueName = uniqid() . "_" . $name;
$path = $uploadDir . $uniqueName;

if (!move_uploaded_file($tmp, $path)) {
    echo json_encode(["status" => false, "message" => "File upload failed"]);
    exit;
}

/* ===== INSERT DB (NO file_size) ===== */
$insert = $conn->prepare("
    INSERT INTO job_files
    (job_id, freelancer_id, file_name, file_path, file_type, uploaded_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$insert->bind_param(
    "iisss",
    $job_id,
    $freelancer_id,
    $name,
    $uniqueName,
    $type
);
$insert->execute();

echo json_encode([
    "status" => true,
    "message" => "File uploaded successfully"
]);