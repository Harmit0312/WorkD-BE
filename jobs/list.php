<?php
include("../config/db.php");

$result = $conn->query("SELECT * FROM jobs WHERE status='open'");
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
