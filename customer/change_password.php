<?php
// customer/change_password.php
require_once '../auth.php';
$username = check_session();  // any logged-in user
$conn = get_db();
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $cfm = $_POST['confirm']      ?? '';

    if ($new !== $cfm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 4) {
        $error = 'Password must be at least 4 characters.';
    } else {
        // Verify old password
        $s = oci_parse($conn, 'SELECT COUNT(*) AS c FROM users WHERE username=:u AND password=:p');
        oci_bind_by_name($s, ':u', $username);
        oci_bind_by_name($s, ':p', $old);
        oci_execute($s);
        $r = oci_fetch_assoc($s);
        oci_free_statement($s);

        if ($r['C'] == 0) {
            $error = 'Current password is incorrect.';
        } else {
            $u = oci_parse($conn, 'UPDATE users SET password=:p WHERE username=:u');
            oci_bind_by_name($u, ':p', $new);
            oci_bind_by_name($u, ':u', $username);
            oci_execute($u);
            oci_commit($conn);
            oci_free_statement($u);
            $message = 'Password updated successfully.';
        }
    }
}
oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Change Password</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Change Password</h2>
  <div class="card">
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Current Password <input type="password" name="old_password" required></label>
      <label>New Password     <input type="password" name="new_password" required></label>
      <label>Confirm New      <input type="password" name="confirm"      required></label>
      <button type="submit">Update Password</button>
    </form>
  </div>
</div>
</body>
</html>
