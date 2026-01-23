<?php
include("../config/db.php");

$job_id = $_POST['job_id'];
$app_id = $_POST['application_id'];
$freelancer_id = $_POST['freelancer_id'];
$client_id = $_POST['client_id'];
$amount = $_POST['budget'];

$setting = $conn->query(
  "SELECT commission_percentage FROM admin_settings LIMIT 1"
);
$commission_percentage = $setting->fetch_assoc()['commission_percentage'];

$commission = ($amount * $commission_percentage) / 100;
$freelancer_amount = $amount - $commission;

$conn->begin_transaction();

$conn->query(
  "UPDATE applications SET status='accepted' WHERE id=$app_id"
);
$conn->query(
  "UPDATE applications SET status='rejected'
   WHERE job_id=$job_id AND id!=$app_id"
);
$conn->query(
  "UPDATE jobs SET status='assigned' WHERE id=$job_id"
);

$conn->query(
  "INSERT INTO orders
   (job_id,client_id,freelancer_id,amount,
    commission_percentage,commission_amount,freelancer_amount,status)
   VALUES
   ($job_id,$client_id,$freelancer_id,$amount,
    $commission_percentage,$commission,$freelancer_amount,'in_progress')"
);

$conn->commit();

echo json_encode(["message" => "Freelancer assigned"]);
