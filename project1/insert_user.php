<?php
include("role_check_admin.php");
include("dbconnect.php");

$username = $_POST['username'];
$password = $_POST['password'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$role = $_POST['role'];

$sql = "INSERT INTO users (username, password, first_name, last_name)
        VALUES (:username, :password, :first_name, :last_name)";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":username", $username);
oci_bind_by_name($stid, ":password", $password);
oci_bind_by_name($stid, ":first_name", $first_name);
oci_bind_by_name($stid, ":last_name", $last_name);

$result = oci_execute($stid);

if (!$result) {
    $e = oci_error($stid);
    die("Error adding user: " . $e['message']);
}

if ($role == "regular" || $role == "hybrid") {
    $sql2 = "INSERT INTO regular_users (username, registration_date)
             VALUES (:username, SYSDATE)";
    $stid2 = oci_parse($conn, $sql2);
    oci_bind_by_name($stid2, ":username", $username);
    oci_execute($stid2);
}

if ($role == "admin" || $role == "hybrid") {
    $sql3 = "INSERT INTO admin_users (username, start_date)
             VALUES (:username, SYSDATE)";
    $stid3 = oci_parse($conn, $sql3);
    oci_bind_by_name($stid3, ":username", $username);
    oci_execute($stid3);
}

oci_commit($conn);

echo "User added successfully.<br><br>";
echo '<a href="admin.php">Back to Admin Page</a>';
?>
