<?php include("role_check_admin.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
</head>
<body>
    <h2>Add New User</h2>

    <form action="insert_user.php" method="post">
        Username:
        <input type="text" name="username" required><br><br>

        Password:
        <input type="text" name="password" required><br><br>

        First Name:
        <input type="text" name="first_name" required><br><br>

        Last Name:
        <input type="text" name="last_name" required><br><br>

        Role:
        <select name="role">
            <option value="regular">Regular</option>
            <option value="admin">Admin</option>
            <option value="hybrid">Hybrid</option>
        </select><br><br>

        <input type="submit" value="Add User">
    </form>

    <br><a href="admin.php">Back to Admin Page</a>
</body>
</html>
