<?
include "verifysession.php";

if ($sessionid == "") {
  echo("Invalid user!");
}
else {
  echo("Hello, welcome to my Website.");
}
?>
