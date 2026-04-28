<?php
include("session_check.php");

if (!$_SESSION['is_regular']) {
    echo "Access denied. Regular users only.";
    exit();
}
?>
