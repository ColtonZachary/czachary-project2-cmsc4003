<?php include("session_check.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Hybrid User Page</title>
</head>
<body>
    <h2>Welcome Hybrid User: <?php echo $_SESSION['username']; ?></h2>

    <a href="regular.php">Go to Regular Page</a><br>
    <a href="admin.php">Go to Admin Page</a><br>
    <a href="change_password.php">Change Password</a><br><br>
    <a href="logout.php">Logout</a>
</body>
</html>
