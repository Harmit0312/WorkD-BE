<?php
// session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ===== HEADERS ===== */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/db.php";

/* ===== INPUT ===== */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No JSON received"]);
    exit;
}

$name       = trim($data['name'] ?? '');
$email      = trim($data['email'] ?? '');
$password   = $data['password'] ?? '';
$role       = $data['role'] ?? '';
$experience = $data['experience'] ?? null;
$skills     = trim($data['skills'] ?? '');

/* ===== BASIC VALIDATION ===== */
if (!$name || !$email || !$password || !$role) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

/* ===== FREELANCER VALIDATION ===== */
if ($role === 'freelancer') {
    if ($experience === null || $skills === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Experience and skills are required for freelancers"
        ]);
        exit;
    }
} else {
    // For clients → keep fields empty
    $experience = null;
    $skills = null;
}

/* ===== CHECK EMAIL ===== */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email already exists"]);
    exit;
}

/* ===== CREATE USER ===== */
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password, role, experience, skills)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "ssssds",
    $name,
    $email,
    $hashed,
    $role,
    $experience,
    $skills
);

if ($stmt->execute()) {

    $user_id = $stmt->insert_id;

    /* ===== CREATE USER ACTIVITY ===== */
    $activity = $conn->prepare(
        "INSERT INTO user_activity (user_id, jobs_posted, proposals_sent, completed_orders)
         VALUES (?, 0, 0, 0)"
    );
    $activity->bind_param("i", $user_id);
    $activity->execute();

    echo json_encode([
        "success" => true,
        "message" => "Registration successful"
    ]);

} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Registration failed"
    ]);
}
