<?
$connection = oci_connect ("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if ($connection == false){
   $e = oci_error(); 
   die($e['message']);
}

$query = "update faculty set worksfor='EE' where facname='Mike'";

$cursor = oci_parse ($connection, $query);

if ($cursor == false){
   $e = oci_error($connection);  
   die($e['message']);
}

$result = oci_execute ($cursor, OCI_NO_AUTO_COMMIT);

if ($result == false){
   $e = oci_error($cursor);  
   die($e['message']);
}

oci_commit ($connection);
oci_close ($connection);

echo ("Record updated.");
?>
