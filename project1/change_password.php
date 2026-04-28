<?php include("session_check.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
</head>
<body>
    <h2>Change Password</h2>

    <form action="update_password.php" method="post">
        New Password:
        <input type="password" name="new_password" required>
        <br><br>
        <input type="submit" value="Update Password">
    </form>

    <br>
    <a href="logout.php">Logout</a>
</body>
</html>
