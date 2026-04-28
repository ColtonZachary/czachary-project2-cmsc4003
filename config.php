<?php
// config.php - Database connection
// Place this file ABOVE the web root, or protect it.

define('DB_USER', 'gq009');
define('DB_PASS', 'xttubb');
define('DB_DSN',  'gqiannew3:1521/orc.uco.local');  // update for your cs2 server

function get_db() {
    $conn = oci_connect(DB_USER, DB_PASS, DB_DSN);
    if (!$conn) {
        $e = oci_error();
        die("Database connection failed: " . $e['message']);
    }
    return $conn;
}
?>
