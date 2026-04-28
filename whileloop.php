<HTML>
<HEAD>
<TITLE>While Loop Example</TITLE>
</HEAD>
<BODY>

<?
echo ("<TABLE ALIGN=CENTER BORDER=1>");

$j = 1;

while ($j <= 4) {

    echo("<TR>");

    $k = 1;

    while ($k <= 2) {
        echo("<TD> Line $j, Cell $k </TD>");
        $k++;
    }

    echo("</TR>");
    $j++;
}

echo("</TABLE>");
?>

</BODY>
</HTML>
