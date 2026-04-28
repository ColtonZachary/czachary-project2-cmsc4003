<?php
// login.php
require_once 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $conn = get_db();

        $sql = 'SELECT u.username, u.first_name,
                       (SELECT COUNT(*) FROM admin_users WHERE username = u.username) AS is_admin,
                       (SELECT COUNT(*) FROM customer WHERE username = u.username) AS is_customer
                FROM users u
                WHERE u.username = :uname AND u.password = :pass';
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':uname', $username);
        oci_bind_by_name($stmt, ':pass',  $password);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        if ($row) {
            // Generate a unique session ID and store it
            $session_id = bin2hex(random_bytes(32));
            $ins = oci_parse($conn, 'INSERT INTO user_sessions VALUES (:sid, :uname, SYSDATE)');
            oci_bind_by_name($ins, ':sid',   $session_id);
            oci_bind_by_name($ins, ':uname', $row['USERNAME']);
            oci_execute($ins);
            oci_commit($conn);

            // Store session info in a cookie
            setcookie('session_id', $session_id, time() + 3600, '/', '', false, true);
            setcookie('username',   $row['USERNAME'],   time() + 3600, '/', '', false, true);

            if ($row['IS_ADMIN'] > 0) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: customer/info.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
        oci_free_statement($stmt);
        oci_close($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Flight Reservation System - Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h1>Flight Reservation System</h1>
  <div class="card">
    <h2>Login</h2>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" action="login.php">
      <label>Username</label>
      <input type="text" name="username" required autofocus>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</div>
</body>
</html>
