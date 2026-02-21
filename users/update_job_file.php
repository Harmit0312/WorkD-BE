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
    echo json_encode([
        "status" => false,
        "message" => "Invalid data"
    ]);
    exit;
}

/* ===== CHECK EXISTING FILES ===== */
$check = $conn->prepare("
    SELECT file_path 
    FROM job_files
    WHERE job_id = ? AND freelancer_id = ?
");
$check->bind_param("ii", $job_id, $freelancer_id);
$check->execute();
$existingFiles = $check->get_result();

/* ===== UPLOAD DIRECTORY ===== */
$uploadDir = __DIR__ . "/../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===== DELETE OLD FILES ===== */
while ($row = $existingFiles->fetch_assoc()) {
    $oldPath = $uploadDir . $row['file_path'];
    if (file_exists($oldPath)) {
        unlink($oldPath);
    }
}

/* ===== DELETE OLD DB ROWS ===== */
$delete = $conn->prepare("
    DELETE FROM job_files
    WHERE job_id = ? AND freelancer_id = ?
");
$delete->bind_param("ii", $job_id, $freelancer_id);
$delete->execute();

/* ===== INSERT NEW FILES ===== */
$fileCount = count($_FILES['files']['name']);

$insert = $conn->prepare("
    INSERT INTO job_files
    (job_id, freelancer_id, file_name, file_path, file_type, uploaded_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

for ($i = 0; $i < $fileCount; $i++) {

    $fileName = $_FILES['files']['name'][$i];
    $tmpName  = $_FILES['files']['tmp_name'][$i];
    $fileType = $_FILES['files']['type'][$i];

    $uniqueName = uniqid() . "_" . $fileName;
    $targetPath = $uploadDir . $uniqueName;

    if (move_uploaded_file($tmpName, $targetPath)) {
        $insert->bind_param(
            "iisss",
            $job_id,
            $freelancer_id,
            $fileName,
            $uniqueName,
            $fileType
        );
        $insert->execute();
    }
}

echo json_encode([
    "status" => true,
    "message" => "Files updated successfully"
]);