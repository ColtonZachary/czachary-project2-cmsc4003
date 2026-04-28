<?php
include("role_check_admin.php");
include("dbconnect.php");

$username = $_POST['username'];
$password = $_POST['password'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];

$sql = "UPDATE users
        SET password = :password,
            first_name = :first_name,
            last_name = :last_name
        WHERE username = :username";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":password", $password);
oci_bind_by_name($stid, ":first_name", $first_name);
oci_bind_by_name($stid, ":last_name", $last_name);
oci_bind_by_name($stid, ":username", $username);
oci_execute($stid);
oci_commit($conn);

echo "User updated successfully.<br><br>";
echo '<a href="list_users.php">Back to User List</a>';
?>
