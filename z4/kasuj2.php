<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'db.php';

$host = strtolower(trim($_POST['host'] ?? ''));
$port = (int)($_POST['port'] ?? 0);

$link = db_connect();
// 13a + 14e: DELETE ... WHERE host i port; zwykły użytkownik usuwa tylko ze swojej puli
if (is_admin()) {
  $stmt = mysqli_prepare($link, 'DELETE FROM domeny WHERE host = ? AND port = ?');
  mysqli_stmt_bind_param($stmt, 'si', $host, $port);
} else {
  $stmt = mysqli_prepare($link, 'DELETE FROM domeny WHERE host = ? AND port = ? AND user_id = ?');
  mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $_SESSION['user_id']);
}
mysqli_stmt_execute($stmt);
$deleted = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

if ($deleted < 1) {
  flash('warning', "Nie znaleziono usługi $host:$port na Twojej liście.");
  redirect('kasuj1.php?' . http_build_query(['host' => $host, 'port' => $port]));
}

flash('success', "Usunięto usługę $host:$port z monitorowania.");
redirect(home_url());
