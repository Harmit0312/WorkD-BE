<?php
require_once "../cors.php";
require_once "../config/db.php";

/* ===== CORS ===== */
// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

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

/* ===== SEARCH ===== */
$search = trim($_GET['search'] ?? "");

$where = "WHERE p.status = 'paid'";
$params = [];
$types = "";

/* SEARCH */
if (!empty($search)) {
    $where .= " AND (
        j.title LIKE ? OR
        c.name LIKE ? OR
        f.name LIKE ?
    )";

    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

/* ===== COUNT TOTAL ===== */
$countSql = "
    SELECT COUNT(*) as total
    FROM orders p
    JOIN jobs j ON p.job_id = j.id
    JOIN users c ON p.client_id = c.id
    JOIN users f ON p.freelancer_id = f.id
    $where
";

$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(ceil($totalRecords / $limit), 1);

/* ===== FETCH EARNINGS ===== */
$sql = "
    SELECT 
        p.id,
        j.title AS job_title,
        c.name AS client_name,
        f.name AS freelancer_name,
        p.amount AS total_price,
        p.commission_amount AS commission_price
    FROM orders p
    JOIN jobs j ON p.job_id = j.id
    JOIN users c ON p.client_id = c.id
    JOIN users f ON p.freelancer_id = f.id
    $where
    ORDER BY p.id DESC
    LIMIT ? OFFSET ?
";



$stmt = $conn->prepare($sql);

/* Add pagination */
$paramsWithLimit = $params;
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;
$typesWithLimit = $types . "ii";

$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();

$result = $stmt->get_result();
$earnings = [];
$totalEarnings = 0;

while ($row = $result->fetch_assoc()) {
    $earnings[] = $row;
    $totalEarnings += $row['commission_price'];
}

/* ===== RESPONSE ===== */
echo json_encode([
    "earnings" => $earnings,
    "total_earnings" => $totalEarnings,
    "total_pages" => $totalPages
]);
