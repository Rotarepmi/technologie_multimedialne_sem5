<?php
// 12, 15, 16: zapis wizyty gościa portalu w tabeli goscieportalu.
// Wywoływany przez visit.js na każdej stronie (także logowania i rejestracji - gość nie musi być zalogowany).
declare(strict_types=1);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit();
}
// Jedna wizyta na sesję; przy wyłączonych ciasteczkach sesja się nie utrzymuje i każde wejście jest logowane
if (!empty($_SESSION['visit_logged'])) {
  http_response_code(204);
  exit();
}

function field(string $name, int $maxLen): ?string
{
  $v = trim((string)($_POST[$name] ?? ''));
  return $v === '' ? null : mb_substr($v, 0, $maxLen);
}

function resolution(string $name): ?string
{
  $v = field($name, 20);
  return $v !== null && preg_match('/^\d{1,5}x\d{1,5}$/', $v) ? $v : null;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$browser = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);
$screen = resolution('screen_res');
$window = resolution('window_res');
$colors = isset($_POST['colors']) && ctype_digit((string)$_POST['colors']) ? min((int)$_POST['colors'], 255) : null;
$cookies = isset($_POST['cookies']) ? (int)($_POST['cookies'] === '1') : null;
$java = isset($_POST['java']) ? (int)($_POST['java'] === '1') : null;
$language = field('language', 35);

require_once 'db.php';
$link = db_connect();
$stmt = mysqli_prepare($link, 'INSERT INTO goscieportalu (ipaddress, browser, screen_res, window_res, colors, cookies, java, language) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'ssssiiis', $ip, $browser, $screen, $window, $colors, $cookies, $java, $language);
if (mysqli_stmt_execute($stmt)) {
  $_SESSION['visit_logged'] = true;
}
mysqli_stmt_close($stmt);
mysqli_close($link);
http_response_code(204);
