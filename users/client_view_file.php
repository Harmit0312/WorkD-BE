<?php
require_once "../cors.php";
require_once "../config/db.php";

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    die("Unauthorized");
}

$client_id = $_SESSION['user_id'];
$file_id = $_GET['file_id'] ?? null;

if (!$file_id) {
    die("Invalid request");
}

/* ===== VERIFY FILE ACCESS ===== */
$stmt = $conn->prepare("
    SELECT jf.file_name, jf.file_path
    FROM job_files jf
    JOIN jobs j ON jf.job_id = j.id
    WHERE jf.id = ? AND j.client_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $file_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found or access denied");
}

$file = $result->fetch_assoc();
$filePath = __DIR__ . "/../uploads/" . $file['file_path'];

if (!file_exists($filePath)) {
    die("File missing from server");
}

/* ===== SHOW FILE IN BROWSER ===== */
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
// finfo_close($finfo);

header("Content-Type: $mimeType");
header("Content-Disposition: inline; filename=\"" . basename($file['file_name']) . "\"");
header("Content-Length: " . filesize($filePath));

readfile($filePath);
exit;