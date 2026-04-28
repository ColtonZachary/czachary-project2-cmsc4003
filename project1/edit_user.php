<?php
include("role_check_admin.php");
include("dbconnect.php");

$username = $_GET['username'];

$sql = "SELECT * FROM users WHERE username = :username";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":username", $username);
oci_execute($stid);

$row = oci_fetch_assoc($stid);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
</head>
<body>
    <h2>Edit User</h2>

    <form action="update_user.php" method="post">
        Username:
        <input type="text" name="username" value="<?php echo $row['USERNAME']; ?>" readonly><br><br>

        Password:
        <input type="text" name="password" value="<?php echo $row['PASSWORD']; ?>" required><br><br>

        First Name:
        <input type="text" name="first_name" value="<?php echo $row['FIRST_NAME']; ?>" required><br><br>

        Last Name:
        <input type="text" name="last_name" value="<?php echo $row['LAST_NAME']; ?>" required><br><br>

        <input type="submit" value="Update User">
    </form>

    <br><a href="list_users.php">Back to User List</a>
</body>
</html>
