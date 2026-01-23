<?php
include("../config/db.php");

$result = $conn->query(
  "SELECT id,name,email,role FROM users WHERE is_active=1"
);
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
