<?php
session_start();
include("dbconnect.php");

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username = :username AND password = :password";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":username", $username);
oci_bind_by_name($stid, ":password", $password);
oci_execute($stid);

$user = oci_fetch_assoc($stid);

if ($user) {
    $session_id = md5(uniqid(rand(), true));

    $insert_sql = "INSERT INTO user_sessions (session_id, username, session_date)
                   VALUES (:session_id, :username, SYSDATE)";
    $insert_stid = oci_parse($conn, $insert_sql);
    oci_bind_by_name($insert_stid, ":session_id", $session_id);
    oci_bind_by_name($insert_stid, ":username", $username);
    oci_execute($insert_stid);
    oci_commit($conn);

    $_SESSION['username'] = $username;
    $_SESSION['session_id'] = $session_id;

    $is_admin = false;
    $is_regular = false;

    $sql_admin = "SELECT * FROM admin_users WHERE username = :username";
    $stid_admin = oci_parse($conn, $sql_admin);
    oci_bind_by_name($stid_admin, ":username", $username);
    oci_execute($stid_admin);
    if (oci_fetch_assoc($stid_admin)) {
        $is_admin = true;
    }

    $sql_regular = "SELECT * FROM regular_users WHERE username = :username";
    $stid_regular = oci_parse($conn, $sql_regular);
    oci_bind_by_name($stid_regular, ":username", $username);
    oci_execute($stid_regular);
    if (oci_fetch_assoc($stid_regular)) {
        $is_regular = true;
    }

    $_SESSION['is_admin'] = $is_admin;
    $_SESSION['is_regular'] = $is_regular;

    if ($is_admin && $is_regular) {
        header("Location: hybrid.php");
    } elseif ($is_admin) {
        header("Location: admin.php");
    } elseif ($is_regular) {
        header("Location: regular.php");
    } else {
        echo "No role assigned to this user.";
    }
    exit();
} else {
    echo "Invalid username or password.";
}
?>
