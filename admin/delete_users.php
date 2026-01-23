<?php
include("../config/db.php");

$user_id = $_POST['user_id'];

$stmt = $conn->prepare(
  "UPDATE users SET is_active=0 WHERE id=?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo json_encode(["message" => "User deactivated"]);
