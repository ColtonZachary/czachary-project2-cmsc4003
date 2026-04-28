<?php
// admin/delete_customer.php
require_once '../auth.php';
check_session('admin');
$u = trim($_GET['u'] ?? '');
if ($u) {
    $conn = get_db();
    // Cascading deletes handle reservation, customer, domestic/foreign sub-tables
    $s = oci_parse($conn, 'DELETE FROM users WHERE username = :u');
    oci_bind_by_name($s, ':u', $u);
    oci_execute($s);
    oci_commit($conn);
    oci_free_statement($s);
    oci_close($conn);
}
header('Location: dashboard.php');
exit;
?>
