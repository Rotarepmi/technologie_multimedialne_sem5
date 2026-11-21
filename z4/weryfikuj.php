<?php
declare(strict_types=1);
$user = trim($_POST['user'] ?? '');
$pass = $_POST['pass'] ?? '';

require_once 'db.php';
$link = db_connect();
$stmt = mysqli_prepare($link, 'SELECT id, username, password, avatar FROM users WHERE username = ?');
mysqli_stmt_bind_param($stmt, 's', $user);
mysqli_stmt_execute($stmt);
$rekord = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
mysqli_close($link);

if (!$rekord || $rekord['password'] !== $pass) {
  header('Location: login.php?error=1');
  exit();
}

session_start();
session_regenerate_id(true);
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = (int)$rekord['id'];          // 14c: id użytkownika do filtrowania tabeli domeny
$_SESSION['username'] = $rekord['username'];
$_SESSION['is_admin'] = $rekord['username'] === 'admin';
$_SESSION['avatar'] = $rekord['avatar'];            // null = domyślna ikona
$_SESSION['sound_on'] = true;                       // 15f: po uruchomieniu dźwięk domyślnie włączony
$_SESSION['sound'] = 1;
header('Location: ' . ($_SESSION['is_admin'] ? 'admin.php' : 'index.php'));
exit();
