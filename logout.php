<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<BODY>
  <?php
  session_start(); // zapewnia dostęp do zmienny sesyjnych w danym pliku
  session_destroy(); // niszczy wszystkie zmienne sesyjne
  header('Location: index3.php');
  exit();
  ?>
</BODY>

</HTML>