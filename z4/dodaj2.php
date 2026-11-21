<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'db.php';
require_once 'monitor.php';

$host = strtolower(trim($_POST['host'] ?? ''));
$port = trim($_POST['port'] ?? '');

if ($error = validate_service($host, $port)) {
  flash('danger', $error);
  redirect('dodaj1.php');
}

$link = db_connect();
$port = (int)$port;
$userId = (int)$_SESSION['user_id'];

// ta sama usługa (host + port) nie powinna być dwa razy na liście jednego użytkownika
$stmt = mysqli_prepare($link, 'SELECT 1 FROM domeny WHERE host = ? AND port = ? AND user_id = ?');
mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $userId);
mysqli_stmt_execute($stmt);
$exists = (bool)mysqli_fetch_row(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($exists) {
  flash('warning', "Usługa $host:$port jest już monitorowana.");
  redirect('dodaj1.php');
}

// 12a + 14d: INSERT INTO z numerycznym id użytkownika
$stmt = mysqli_prepare($link, 'INSERT INTO domeny (host, port, user_id) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $userId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

flash($ok ? 'success' : 'danger', $ok ? "Dodano usługę $host:$port." : 'Nie udało się dodać usługi.');
redirect(home_url());
