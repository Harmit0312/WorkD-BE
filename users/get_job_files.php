<?php
require_once "../cors.php";
require_once "../config/db.php";

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    die("Unauthorized");
}

$client_id = $_SESSION['user_id'];

/* =====================================================
   CASE 1: VIEW FILE IN BROWSER
   ===================================================== */
// if (isset($_GET['file_id'])) {

//     $file_id = intval($_GET['file_id']);

//     $stmt = $conn->prepare("
//         SELECT jf.file_name, jf.file_path, jf.file_type
//         FROM job_files jf
//         JOIN jobs j ON jf.job_id = j.id
//         WHERE jf.id = ? AND j.client_id = ?
//         LIMIT 1
//     ");
//     $stmt->bind_param("ii", $file_id, $client_id);
//     $stmt->execute();
//     $res = $stmt->get_result();

//     if ($res->num_rows === 0) {
//         die("File not found or access denied");
//     }

//     $file = $res->fetch_assoc();
//     $filePath = __DIR__ . "/../uploads/" . $file['file_path'];

//     if (!file_exists($filePath)) {
//         die("File missing");
//     }

//     header("Content-Type: " . $file['file_type']);
//     header("Content-Disposition: inline; filename=\"" . basename($file['file_name']) . "\"");
//     header("Content-Length: " . filesize($filePath));

//     readfile($filePath);
//     exit;
// }

/* =====================================================
   CASE 2: FETCH FILE LIST FOR A JOB
   ===================================================== */
if (!isset($_GET['job_id'])) {
    echo json_encode(["status" => false, "message" => "Job ID missing"]);
    exit;
}

$job_id = intval($_GET['job_id']);

/* ===== VERIFY JOB OWNERSHIP ===== */
$checkJob = $conn->prepare("
    SELECT id FROM jobs
    WHERE id = ? AND client_id = ?
    LIMIT 1
");
$checkJob->bind_param("ii", $job_id, $client_id);
$checkJob->execute();

if ($checkJob->get_result()->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "Invalid job"]);
    exit;
}

/* ===== FETCH FILES ===== */
$getFiles = $conn->prepare("
    SELECT id, file_name, file_type, uploaded_at
    FROM job_files
    WHERE job_id = ?
    ORDER BY uploaded_at DESC
");
$getFiles->bind_param("i", $job_id);
$getFiles->execute();

$result = $getFiles->get_result();
$files = [];

while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}

echo json_encode([
    "status" => true,
    "files" => $files
]);