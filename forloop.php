<HTML>
<HEAD>
<TITLE>Example of For Loop</TITLE>
</HEAD>
<BODY>

<?
echo ("<TABLE ALIGN=CENTER BORDER=1>");

for ($j=1; $j<=4; $j++) {
  echo ("<TR>");
  for ($k=1; $k<=2; $k++)
    echo ("<TD> Line $j, Cell $k </TD>");
  echo("</TR>");
}

echo ("</TABLE>");
?>

</BODY>
</HTML>
