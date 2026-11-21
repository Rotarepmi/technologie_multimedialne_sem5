<?php
// Punkt 3: sprawdzenie, czy hosting pozwala na fsockopen()
declare(strict_types=1);
require_once 'auth.php';

$host = 'fb.com';
$port = 80;
$fp = @fsockopen($host, $port, $errno, $errstr, 30);
echo 'Host ' . $host . ':' . $port . ' jest: ';
if ($fp) {
  echo 'OK';
  fclose($fp);
} else {
  echo 'Awaria ' . htmlspecialchars("$errstr ($errno)");
}
echo '<br><a href="' . home_url() . '">Powrót</a>';
