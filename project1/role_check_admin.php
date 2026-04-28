<?php
include("session_check.php");

if (!$_SESSION['is_admin']) {
    echo "Access denied. Admin users only.";
    exit();
}
?>
