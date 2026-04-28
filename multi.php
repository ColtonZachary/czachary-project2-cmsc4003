<html>
<head><title>Student Page</title></head>
<body>

<?
$queryCno = $_POST["queryCno"];

// build WHERE clause
if(!isset($queryCno) or ($queryCno==""))
  $whereClause = " 1=1 ";
else{
  $queryCno = strtoupper($queryCno);
  $whereClause = " cno like '%$queryCno%' ";
}

// connect to Oracle
$connection = oci_connect("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");
if($connection == false){
  $e = oci_error();
  die($e['message']);
}

// form #1: filter box (recursive form calls multi.php again)
echo("<FORM name=\"queryCourse\" method=\"POST\" action=\"multi.php\"> " .
     "Course No: <INPUT type=\"text\" name=\"queryCno\"> " .
     "<INPUT type=\"submit\" name=\"btnSubmit\" value=\"Query\"> " .
     "</FORM>");

// query courses
$query = "select cno, credits from CourseDescription where " . $whereClause;
$cursor = oci_parse($connection, $query);
if($cursor == false){
  $e = oci_error($connection);
  die($e['message']);
}

$result = oci_execute($cursor);
if($result == false){
  $e = oci_error($cursor);
  die($e['message']);
}

// form #2: checkbox list posts to proc.php
echo "<form action=\"proc.php\" method=\"post\">";
echo "<table border=1>";
echo "<tr><td><b>Course No</b></td><td><b>Credits</b></td><td></td></tr>";

while($values = oci_fetch_array($cursor)){
  $cno = $values[0];
  $credits = $values[1];

  echo "<tr>";
  echo "<td>$cno</td>";
  echo "<td>$credits</td>";
  echo "<td><input type=\"checkbox\" name=\"cnoList[]\" value=\"$cno\"></td>";
  echo "</tr>";
}

echo "</table>";
echo "<INPUT type=\"submit\" name=\"btnSubmit\" value=\"Proc\">";
echo "</form>";

oci_free_statement($cursor);
oci_close($connection);
?>

</body>
</html>
