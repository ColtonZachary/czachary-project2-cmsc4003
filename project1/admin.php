<?php include("role_check_admin.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin User Page</title>
</head>
<body>
    <h2>Welcome Admin User: <?php echo $_SESSION['username']; ?></h2>

    <a href="list_users.php">List All Users</a><br>
    <a href="search_user.php">Search Users</a><br>
    <a href="add_user.php">Add User</a><br>
    <a href="change_password.php">Change Password</a><br><br>
    <a href="logout.php">Logout</a>
</body>
</html>
