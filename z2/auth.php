<?php
declare(strict_types=1);
session_start(); // zapewnia dostęp do zmiennych sesyjnych w danym pliku
if (!isset($_SESSION['loggedin'])) {
  header('Location: login.php');
  exit();
}
