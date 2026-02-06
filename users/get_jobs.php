<?php
require_once "../config/db.php";

/* ===== CORS ===== */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===== INPUT ===== */
$search = $_GET['search'] ?? '';
$budget = $_GET['budget'] ?? '';

$where = "WHERE j.status = 'open'";
$params = [];
$types = "";

/* SEARCH */
if ($search !== '') {
    $where .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

/* BUDGET FILTER */
if ($budget === 'low') {
    $where .= " AND j.budget < 50000";
} elseif ($budget === 'mid') {
    $where .= " AND j.budget BETWEEN 50000 AND 100000";
} elseif ($budget === 'high') {
    $where .= " AND j.budget > 100000";
}

/* ===== QUERY ===== */
$sql = "
SELECT 
  j.id,
  j.title,
  j.description,
  j.budget,
  j.deadline,
  u.name AS client_name
FROM jobs j
JOIN users u ON j.client_id = u.id
$where
ORDER BY j.id DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

echo json_encode([
    "status" => true,
    "jobs" => $jobs
]);
