<?php
include("../config/db.php");

$order_id = $_POST['order_id'];
$rating = $_POST['rating'];
$comment = $_POST['comment'];

$stmt = $conn->prepare(
  "INSERT INTO reviews (order_id,rating,comment) VALUES (?,?,?)"
);
$stmt->bind_param("iis", $order_id, $rating, $comment);
$stmt->execute();

echo json_encode(["message" => "Review submitted"]);
