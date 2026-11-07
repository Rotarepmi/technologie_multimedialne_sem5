<?php
declare(strict_types=1);
$user = trim($_POST['user'] ?? '');
$pass = $_POST['pass'] ?? '';

require_once 'db.php';
$link = db_connect();
$stmt = mysqli_prepare($link, 'SELECT username, password, avatar FROM users WHERE username = ?');
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
$_SESSION['loggedin'] = true;
$_SESSION['username'] = $rekord['username'];
$_SESSION['avatar'] = $rekord['avatar']; // null = domyślna ikona
header('Location: index.php');
exit();
