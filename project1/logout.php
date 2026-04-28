<?php
session_start();
include("dbconnect.php");

if (isset($_SESSION['session_id'])) {
    $session_id = $_SESSION['session_id'];

    $sql = "DELETE FROM user_sessions WHERE session_id = :session_id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":session_id", $session_id);
    oci_execute($stid);
    oci_commit($conn);
}

session_destroy();
header("Location: login.html");
exit();
?>
