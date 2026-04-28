<?php include("role_check_regular.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Regular User Page</title>
</head>
<body>
    <h2>Welcome Regular User: <?php echo $_SESSION['username']; ?></h2>

    <a href="change_password.php">Change Password</a><br><br>
    <a href="logout.php">Logout</a>
</body>
</html>
