<?

$connection = oci_connect("gq009", "xttubb", "gqiannew3:1521/orc.uco.local");

if (!$connection) {
    $e = oci_error();
    die($e['message']);
}

$query = "BEGIN firstThree(:my_seqid, :my_average); END;";

$cursor = oci_parse($connection, $query);

if (!$cursor) {
    $e = oci_error($connection);
    die($e['message']);
}

$my_seqid = '00001';
$my_average = 0;

// Bind input
oci_bind_by_name($cursor, ":my_seqid", $my_seqid, 5);

// Bind output
oci_bind_by_name($cursor, ":my_average", $my_average, 40);

$result = oci_execute($cursor, OCI_NO_AUTO_COMMIT);

if (!$result) {
    $e = oci_error($cursor);
    die($e['message']);
}

oci_commit($connection);

echo "Average of top 3 grades: " . $my_average;

oci_close($connection);

?>