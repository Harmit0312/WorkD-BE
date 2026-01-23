<?php
include("../config/db.php");

$id = $_POST['user_id'];
$name = $_POST['name'];
$email = $_POST['email'];

$stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
$stmt->bind_param("ssi", $name, $email, $id);
$stmt->execute();

echo json_encode(["message" => "Profile updated"]);
