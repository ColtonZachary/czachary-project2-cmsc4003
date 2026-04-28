<?php
include("role_check_admin.php");
include("dbconnect.php");

if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Delete sessions first
    $sql1 = "DELETE FROM user_sessions WHERE username = :username";
    $stid1 = oci_parse($conn, $sql1);
    oci_bind_by_name($stid1, ":username", $username);
    oci_execute($stid1);

    // Delete regular user row if it exists
    $sql2 = "DELETE FROM regular_users WHERE username = :username";
    $stid2 = oci_parse($conn, $sql2);
    oci_bind_by_name($stid2, ":username", $username);
    oci_execute($stid2);

    // Delete admin user row if it exists
    $sql3 = "DELETE FROM admin_users WHERE username = :username";
    $stid3 = oci_parse($conn, $sql3);
    oci_bind_by_name($stid3, ":username", $username);
    oci_execute($stid3);

    // Delete main user row last
    $sql4 = "DELETE FROM users WHERE username = :username";
    $stid4 = oci_parse($conn, $sql4);
    oci_bind_by_name($stid4, ":username", $username);
    $result = oci_execute($stid4);

    if (!$result) {
        $e = oci_error($stid4);
        die("Error deleting user: " . $e['message']);
    }

    oci_commit($conn);

    echo "User deleted successfully.<br><br>";
    echo '<a href="list_users.php">Back to User List</a>';
}
?>
