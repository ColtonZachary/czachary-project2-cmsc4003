<?
$clientid = $_POST["clientid"];

$connection = oci_connect ("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if($connection == false){
  $e = oci_error();
  die($e['message']);
}

// lookup the client
$sql = "select clientid from client where clientid='$clientid'";
$cursor = oci_parse($connection, $sql);

if ($cursor == false) {
  $e = oci_error($connection);
  oci_close($connection);
  die("Client Query Failed: " . $e['message']);
}

$result = oci_execute($cursor);
if ($result == false){
  $e = oci_error($cursor);
  oci_close($connection);
  die("Client Query Failed: " . $e['message']);
}

if(!$values = oci_fetch_array($cursor)){
  oci_close($connection);
  die("Client not found.");
}

oci_free_statement($cursor);

// found client
$clientid = $values[0];

// create new session id
$sessionid = md5(uniqid(rand()));

// insert session record
$sql = "insert into clientsession (sessionid, clientid, sessiondate)
        values ('$sessionid', '$clientid', sysdate)";

$cursor = oci_parse($connection, $sql);
if ($cursor == false) {
  $e = oci_error($connection);
  oci_close($connection);
  die("Failed to create a new session: " . $e['message']);
}

$result = oci_execute($cursor);
if ($result == false){
  $e = oci_error($cursor);
  oci_close($connection);
  die("Failed to create a new session: " . $e['message']);
}

oci_commit($connection);
oci_close($connection);

// redirect
Header("Location: welcomepage.php?sessionid=$sessionid");
?>
