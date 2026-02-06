<?php
require_once "../config/db.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$search = $_GET['search'] ?? '';
$role   = $_GET['role'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 4;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
$types = "";

/* SEARCH */
if ($search !== '') {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

/* ROLE FILTER */
if ($role === 'deleted') {
    $where .= " AND deleted_at IS NOT NULL";
} elseif ($role !== 'all') {
    $where .= " AND role = ? AND deleted_at IS NULL";
    $params[] = $role;
    $types .= "s";
} else {
    $where .= " AND deleted_at IS NULL";
}

/* COUNT */
$countSql = "SELECT COUNT(*) FROM users $where";
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();

/* DATA */
$sql = "
SELECT 
  u.id, u.name, u.email, u.role, u.avatar, u.join_date, u.deleted_at,
  ua.jobs_posted, ua.proposals_sent, ua.completed_orders
FROM users u
LEFT JOIN user_activity ua ON ua.user_id = u.id
$where
ORDER BY u.id DESC
LIMIT ? OFFSET ?
";

$dataParams = $params;
$dataTypes  = $types;

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    "status" => true,
    "users" => $users,
    "total_pages" => ceil($total / $limit)
]);
