<?php
include("role_check_admin.php");
include("dbconnect.php");

$sql = "SELECT * FROM users ORDER BY username";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

echo "<h2>All Users</h2>";
echo "<table border='1'>";
echo "<tr><th>Username</th><th>First Name</th><th>Last Name</th><th>Actions</th></tr>";

while ($row = oci_fetch_assoc($stid)) {
    echo "<tr>";
    echo "<td>" . $row['USERNAME'] . "</td>";
    echo "<td>" . $row['FIRST_NAME'] . "</td>";
    echo "<td>" . $row['LAST_NAME'] . "</td>";
    echo "<td>
            <a href='edit_user.php?username=" . $row['USERNAME'] . "'>Edit</a> |
            <a href='delete_user.php?username=" . $row['USERNAME'] . "'>Delete</a>
          </td>";
    echo "</tr>";
}

echo "</table>";
echo "<br><a href='admin.php'>Back to Admin Page</a>";
?>
