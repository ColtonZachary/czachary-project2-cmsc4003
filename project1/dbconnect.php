<?php
$conn = oci_connect("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}
?>
