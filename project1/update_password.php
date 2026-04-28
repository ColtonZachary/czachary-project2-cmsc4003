<?php
include("session_check.php");
include("dbconnect.php");

$username = $_SESSION['username'];
$new_password = $_POST['new_password'];

$sql = "UPDATE users SET password = :new_password WHERE username = :username";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":new_password", $new_password);
oci_bind_by_name($stid, ":username", $username);
oci_execute($stid);
oci_commit($conn);

echo "Password updated successfully.<br><br>";
echo '<a href="logout.php">Logout</a>';
?>
