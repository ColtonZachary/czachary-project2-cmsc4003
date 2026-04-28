<?
$connection = oci_connect ("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if ($connection == false){
   $e = oci_error(); 
   die($e['message']);
}

$query = "select sname, ssno, stulevel from student where curaddress='OKC'";

$cursor = oci_parse ($connection, $query);

if ($cursor == false){
   $e = oci_error($connection);  
   die($e['message']);
}

$result = oci_execute ($cursor);

if ($result == false){
   $e = oci_error($cursor);  
   die($e['message']);
}

echo "<table border=1>";
echo "<tr><th>Name</th><th>SSN</th><th>Level</th></tr>";

while ($values = oci_fetch_array ($cursor)){

  echo "<tr>";
  echo "<td>$values[0]</td>";
  echo "<td>$values[1]</td>";
  echo "<td>$values[2]</td>";
  echo "</tr>";
}

echo "</table>";

oci_free_statement($cursor);
oci_close ($connection);
?>
