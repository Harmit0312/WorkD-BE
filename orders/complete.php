<?php
include("../config/db.php");

$order_id = $_POST['order_id'];

$conn->query("UPDATE orders SET status='completed' WHERE id=$order_id");

echo json_encode(["message" => "Order completed"]);
