<?php

require_once "../cors.php";
require_once "../config/db.php";

header("Content-Type: application/json");

// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || trim($data['email']) === '') {
    echo json_encode([
        "status" => false,
        "message" => "Email is required"
    ]);
    exit;
}

$email = trim($data['email']);

/* 🔥 Prevent self deletion */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => false,
        "message" => "Admin not found"
    ]);
    exit;
}

$admin = $result->fetch_assoc();

if ($admin['id'] == $_SESSION['user_id']) {
    echo json_encode([
        "status" => false,
        "message" => "You cannot delete yourself"
    ]);
    exit;
}

/* Delete admin */
$stmt = $conn->prepare("DELETE FROM users WHERE email = ? AND role = 'admin'");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Admin deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Database error"
    ]);
}
