<?php include("role_check_admin.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Search User</title>
</head>
<body>
    <h2>Search User</h2>

    <form method="get" action="search_user.php">
        Username:
        <input type="text" name="username">
        <input type="submit" value="Search">
    </form>

    <br><a href="admin.php">Back to Admin Page</a>
</body>
</html>

<?php
include("dbconnect.php");

if (isset($_GET['username']) && $_GET['username'] != "") {
    $username = $_GET['username'];

    $sql = "SELECT * FROM users WHERE username = :username";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":username", $username);
    oci_execute($stid);

    if ($row = oci_fetch_assoc($stid)) {
        echo "<hr>";
        echo "<p><strong>Username:</strong> " . $row['USERNAME'] . "</p>";
        echo "<p><strong>First Name:</strong> " . $row['FIRST_NAME'] . "</p>";
        echo "<p><strong>Last Name:</strong> " . $row['LAST_NAME'] . "</p>";
    } else {
        echo "<hr><p>User not found.</p>";
    }
}
?>
