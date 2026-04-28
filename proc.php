<?
$cnoList = $_POST["cnoList"];

if(!isset($cnoList) || count($cnoList) == 0){
  echo "No courses selected.<br>";
  exit;
}

$numOfCno = count($cnoList);

for($n=0; $n<$numOfCno; $n++){
  echo "$cnoList[$n]<br>";
}
?>
