<?
$sessionid = $_GET["sessionid"];
$clientid = "";

$connection = oci_connect ("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if($connection == false){
  $sessionid = "";
}
else{
  if (!isset($sessionid) || $sessionid == ""){
    $sessionid = "";
  }
  else{
    $sql = "select clientid from clientsession where sessionid='$sessionid'";
    $cursor = oci_parse($connection, $sql);

    if($cursor == false){
      $sessionid = "";
    }
    else{
      $result = oci_execute($cursor);
      if($result == false){
        $sessionid = "";
      }
      else{
        if($values = oci_fetch_array($cursor)){
          $clientid = $values[0];
        }
        else{
          $sessionid = "";
        }
      }
      oci_free_statement($cursor);
    }
  }
  oci_close($connection);
}
?>
