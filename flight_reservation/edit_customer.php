<?php
// admin/edit_customer.php
require_once '../auth.php';
check_session('admin');
$conn = get_db();
$message = $error = '';
$u = trim($_GET['u'] ?? $_POST['username'] ?? '');

// Fetch current data
$s = oci_parse($conn, 'SELECT u.first_name, u.last_name, u.password,
                               c.phone_number, c.cust_type, c.diamond_status
                        FROM users u JOIN customer c ON u.username=c.username
                        WHERE u.username=:u');
oci_bind_by_name($s, ':u', $u);
oci_execute($s);
$info = oci_fetch_assoc($s);
oci_free_statement($s);

if (!$info) {
    echo "<p class='error'>Customer not found.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first   = trim($_POST['first_name']   ?? '');
    $last    = trim($_POST['last_name']    ?? '');
    $pass    = trim($_POST['password']     ?? '');
    $phone   = trim($_POST['phone']        ?? '');
    $diamond = (int)($_POST['diamond']     ?? 0);

    $s = oci_parse($conn, 'UPDATE users SET first_name=:fn, last_name=:ln, password=:p WHERE username=:u');
    oci_bind_by_name($s, ':fn', $first);
    oci_bind_by_name($s, ':ln', $last);
    oci_bind_by_name($s, ':p',  $pass);
    oci_bind_by_name($s, ':u',  $u);
    oci_execute($s); oci_free_statement($s);

    $s = oci_parse($conn, 'UPDATE customer SET phone_number=:ph, diamond_status=:d WHERE username=:u');
    oci_bind_by_name($s, ':ph', $phone);
    oci_bind_by_name($s, ':d',  $diamond);
    oci_bind_by_name($s, ':u',  $u);
    oci_execute($s); oci_free_statement($s);
    oci_commit($conn);
    $message = "Customer updated.";

    // Refresh info
    $s = oci_parse($conn, 'SELECT u.first_name, u.last_name, u.password,
                                  c.phone_number, c.cust_type, c.diamond_status
                           FROM users u JOIN customer c ON u.username=c.username
                           WHERE u.username=:u');
    oci_bind_by_name($s, ':u', $u);
    oci_execute($s);
    $info = oci_fetch_assoc($s);
    oci_free_statement($s);
}
oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Edit Customer</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Edit Customer: <?= htmlspecialchars($u) ?></h2>
  <div class="card">
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
      <label>First Name <input type="text" name="first_name" value="<?= htmlspecialchars($info['FIRST_NAME']) ?>" required></label>
      <label>Last Name  <input type="text" name="last_name"  value="<?= htmlspecialchars($info['LAST_NAME']) ?>"  required></label>
      <label>Password   <input type="password" name="password" value="<?= htmlspecialchars($info['PASSWORD']) ?>" required></label>
      <label>Phone      <input type="text" name="phone" value="<?= htmlspecialchars($info['PHONE_NUMBER']) ?>" required></label>
      <label>Diamond Status
        <select name="diamond">
          <option value="0" <?= $info['DIAMOND_STATUS']==0?'selected':'' ?>>Standard</option>
          <option value="1" <?= $info['DIAMOND_STATUS']==1?'selected':'' ?>>Diamond</option>
        </select>
      </label>
      <button type="submit">Save Changes</button>
      <a class="btn" href="dashboard.php">Cancel</a>
    </form>
  </div>
</div>
</body>
</html>
