<?
$connection = oci_connect ("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if ($connection == false){
   $e = oci_error(); 
   die($e['message']);
}

$query = "select * from faculty";

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
echo "<tr> <th>Name</th> <th>SSN</th> <th>Address</th> <th>Department</th> </tr>";

while ($values = oci_fetch_array ($cursor)){

  $name = $values[0];
  $ssn = $values[1];
  $address = $values[2];
  $dept = $values[3];

  echo "<tr>";
  echo "<td>$name</td>";
  echo "<td>$ssn</td>";
  echo "<td>$address</td>";
  echo "<td>$dept</td>";
  echo "</tr>";
}

echo "</table>";

oci_free_statement($cursor);
oci_close ($connection);
?>
