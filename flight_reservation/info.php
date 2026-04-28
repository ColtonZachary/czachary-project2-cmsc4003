<?php
// customer/info.php - Customer Personal Information
require_once '../auth.php';
$username = check_session('customer');
$conn = get_db();

$sql = 'SELECT u.username, u.password, u.first_name, u.last_name,
               c.phone_number, c.cust_type, c.diamond_status, c.reg_date
        FROM users u
        JOIN customer c ON u.username = c.username
        WHERE u.username = :uname';
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':uname', $username);
oci_execute($stmt);
$info = oci_fetch_assoc($stmt);
oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>My Profile</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Personal Information</h2>
  <div class="card">
    <table class="info-table">
      <tr><th>Username</th>    <td><?= htmlspecialchars($info['USERNAME']) ?></td></tr>
      <tr><th>Password</th>    <td>••••••••</td></tr>
      <tr><th>First Name</th>  <td><?= htmlspecialchars($info['FIRST_NAME']) ?></td></tr>
      <tr><th>Last Name</th>   <td><?= htmlspecialchars($info['LAST_NAME']) ?></td></tr>
      <tr><th>Phone</th>       <td><?= htmlspecialchars($info['PHONE_NUMBER']) ?></td></tr>
      <tr><th>Type</th>        <td><?= ucfirst(htmlspecialchars($info['CUST_TYPE'])) ?></td></tr>
      <tr><th>Diamond Status</th><td><?= $info['DIAMOND_STATUS'] ? 'Diamond Customer' : 'Standard' ?></td></tr>
    </table>
    <a class="btn" href="change_password.php">Change Password</a>
  </div>
</div>
</body>
</html>
