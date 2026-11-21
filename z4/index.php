<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
if (is_admin()) {
  redirect('admin.php');
}
require_once 'db.php';
require_once 'layout.php';
require_once 'monitor.php';

// 14b-c: zwykły użytkownik widzi tylko usługi przypisane do swojego id
$link = db_connect();
$stmt = mysqli_prepare($link, 'SELECT id, host, port, errors, failure, total_errors FROM domeny WHERE user_id = ? ORDER BY id');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

page_start('Monitorowane usługi');
render_monitor($link, $rows, false);
page_end();
mysqli_close($link);
