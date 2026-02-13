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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== PAGINATION ===== */
$page = max((int)($_GET['page'] ?? 1), 1);
$limit = 6;
$offset = ($page - 1) * $limit;

/* ===== FILTER & SEARCH ===== */
$filter = $_GET['filter'] ?? "all";
$search = trim($_GET['search'] ?? "");

$where = "WHERE j.status != 'deleted' AND j.admin_deleted = 0";
$params = [];
$types = "";

/* SEARCH */
if (!empty($search)) {
    $where .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

/* FILTER */
if ($filter === "client") {
    $where .= " AND j.status = 'open'";
} elseif ($filter === "freelancer") {
    $where .= " AND j.status IN ('assigned','completed','paid')";
}

/* ===== COUNT QUERY ===== */
$countSql = "SELECT COUNT(*) as total FROM jobs j $where";
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalJobs = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(ceil($totalJobs / $limit), 1);

/* ===== FETCH JOBS ===== */
$sql = "
    SELECT 
        j.id,
        j.title,
        j.description,
        j.budget,
        j.status,
        j.deadline,
        c.name AS client_name,
        (
            SELECT u.name
            FROM applications a
            JOIN users u ON u.id = a.freelancer_id
            WHERE a.job_id = j.id 
            AND a.status = 'accepted'
            LIMIT 1
        ) AS assigned_freelancer_name
    FROM jobs j
    JOIN users c ON j.client_id = c.id
    $where
    ORDER BY j.id DESC
    LIMIT ? OFFSET ?
";


$stmt = $conn->prepare($sql);

/* Add pagination params */
$paramsWithLimit = $params;
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;
$typesWithLimit = $types . "ii";

$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();

$result = $stmt->get_result();
$jobs = [];

while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

/* ===== RESPONSE ===== */
echo json_encode([
    "status" => true,
    "jobs" => $jobs,
    "total_pages" => $totalPages
]);
