<?php
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query(
      "SELECT commission_percentage FROM admin_settings LIMIT 1"
    );
    echo json_encode($res->fetch_assoc());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commission = $_POST['commission_percentage'];
    $stmt = $conn->prepare(
      "UPDATE admin_settings SET commission_percentage=?"
    );
    $stmt->bind_param("i", $commission);
    $stmt->execute();
    echo json_encode(["message" => "Commission updated"]);
}
