<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['username']) || !isset($_SESSION['session_id'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
$session_id = $_SESSION['session_id'];

$sql = "SELECT * FROM user_sessions WHERE session_id = :session_id AND username = :username";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":session_id", $session_id);
oci_bind_by_name($stid, ":username", $username);
oci_execute($stid);

if (!oci_fetch_assoc($stid)) {
    session_destroy();
    header("Location: login.html");
    exit();
}
?>
