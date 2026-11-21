<?php
declare(strict_types=1);
require_once 'auth.php';
require_admin();
require_once 'db.php';
require_once 'monitor.php';

$link = db_connect();
$action = $_POST['action'] ?? '';

if ($action === 'toggle') {
  // nadanie / odebranie roli pracownika (admin nie może być pracownikiem)
  $userId = (int)($_POST['user_id'] ?? 0);
  $stmt = mysqli_prepare($link, "UPDATE users SET worker = NOT worker WHERE id = ? AND username <> 'admin'");
  mysqli_stmt_bind_param($stmt, 'i', $userId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  flash('success', 'Zmieniono rolę użytkownika.');
  redirect('staff.php');
}

if ($action === 'assign') {
  $host = strtolower(trim($_POST['host'] ?? ''));
  $port = trim($_POST['port'] ?? '');
  $workerId = (int)($_POST['worker_id'] ?? 0);

  if ($error = validate_service($host, $port)) {
    flash('danger', $error);
    redirect('staff.php');
  }

  // przypisać można tylko faktycznego pracownika
  $stmt = mysqli_prepare($link, 'SELECT username FROM users WHERE id = ? AND worker = TRUE');
  mysqli_stmt_bind_param($stmt, 'i', $workerId);
  mysqli_stmt_execute($stmt);
  $worker = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  mysqli_stmt_close($stmt);
  if (!$worker) {
    flash('danger', 'Wybrany użytkownik nie jest pracownikiem.');
    redirect('staff.php');
  }

  $port = (int)$port;
  $stmt = mysqli_prepare($link, 'INSERT INTO domeny (host, port, user_id) VALUES (?, ?, ?)');
  mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $workerId);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  flash($ok ? 'success' : 'danger', $ok ? "Usługa $host:$port przypisana do: {$worker['username']}." : 'Nie udało się przypisać usługi.');
  redirect('staff.php');
}

redirect('staff.php');
