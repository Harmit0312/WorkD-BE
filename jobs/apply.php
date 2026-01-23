<?php
include("../config/db.php");

$job_id = $_POST['job_id'];
$freelancer_id = $_POST['freelancer_id'];
$proposal = $_POST['proposal'];

$stmt = $conn->prepare(
  "INSERT INTO applications (job_id,freelancer_id,proposal)
   VALUES (?,?,?)"
);
$stmt->bind_param("iis", $job_id, $freelancer_id, $proposal);
$stmt->execute();

echo json_encode(["message" => "Applied successfully"]);
