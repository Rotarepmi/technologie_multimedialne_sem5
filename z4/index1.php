<?php
// Punkty 6-11: surowa wersja z instrukcji - tabela domeny + stan połączenia, auto-odświeżanie co 10 s
declare(strict_types=1);
require_once 'auth.php';
require_once 'db.php';
?>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="10" />
  <title>index1.php</title>
</head>
<body>
<?php
$polaczenie = db_connect(); // przy błędzie db_connect() wyświetla komunikat i kończy skrypt
$rezultat = mysqli_query($polaczenie, 'SELECT id, host, port FROM domeny') or die('Błąd zapytania do bazy');
print '<TABLE CELLPADDING=5 BORDER=1>';
print "<TR><TD>id</TD><TD>Host</TD><TD>Port</TD><TD>Stan</TD></TR>\n";
while ($wiersz = mysqli_fetch_array($rezultat)) {
  $id = $wiersz[0];
  $host = htmlspecialchars($wiersz[1]);
  $port = $wiersz[2];
  $fp = @fsockopen($wiersz[1], (int)$port, $errno, $errstr, 3);
  if ($fp) { $stan = 'Ok'; fclose($fp); } else { $stan = htmlspecialchars("$errno $errstr"); }
  print "<TR><TD>$id</TD><TD>$host</TD><TD>$port</TD><TD>$stan</TD></TR>\n";
}
print '</TABLE>';
mysqli_close($polaczenie);
?>
<p><a href="<?= home_url() ?>">Powrót do aplikacji</a></p>
</body>
</html>
