<?php
include("../config/db.php");

$client_id = $_POST['client_id'];
$title = $_POST['title'];
$description = $_POST['description'];
$budget = $_POST['budget'];

$stmt = $conn->prepare(
  "INSERT INTO jobs (client_id,title,description,budget,status)
   VALUES (?,?,?,?, 'open')"
);
$stmt->bind_param("issi", $client_id, $title, $description, $budget);
$stmt->execute();

echo json_encode(["message" => "Job created"]);
