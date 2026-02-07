<?php
require_once "../config/db.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

/* ===== PAGINATION ===== */
$search = $_GET['search'] ?? '';
$role   = $_GET['role'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 4;
$offset = ($page - 1) * $limit;

/* ===== BASE FILTER ===== */
$where = "WHERE u.role != 'admin'";
$params = [];
$types  = "";

/* ===== SEARCH ===== */
if ($search !== '') {
    $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

/* ===== ROLE FILTER ===== */
if ($role === 'deleted') {
    $where .= " AND u.deleted_at IS NOT NULL";
} elseif ($role !== 'all') {
    $where .= " AND u.role = ? AND u.deleted_at IS NULL";
    $params[] = $role;
    $types   .= "s";
} else {
    $where .= " AND u.deleted_at IS NULL";
}

/* ===== COUNT (MUST MATCH DATA QUERY) ===== */
/* ===== COUNT ===== */
$countSql = "
    SELECT COUNT(*)
    FROM users u
    LEFT JOIN user_activity ua ON ua.user_id = u.id
    $where
";

$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();


/* ===== DATA QUERY ===== */
$sql = "
SELECT 
    u.id, u.name, u.email, u.role, u.avatar, u.join_date, u.deleted_at,
    IFNULL(ua.jobs_posted, 0) AS jobs_posted,
    IFNULL(ua.proposals_sent, 0) AS proposals_sent,
    IFNULL(ua.completed_orders, 0) AS completed_orders
FROM users u
LEFT JOIN user_activity ua ON ua.user_id = u.id
$where
ORDER BY u.id DESC
LIMIT ? OFFSET ?
";

/* merge params */
$dataParams = $params;
$dataTypes  = $types;

$dataParams[] = $limit;
$dataParams[] = $offset;
$dataTypes   .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($dataTypes, ...$dataParams);
$stmt->execute();

$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    "status" => true,
    "users" => $users,
    "current_page" => $page,
    "total_pages" => ceil($total / $limit)
]);
