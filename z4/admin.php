<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
require_admin();
require_once 'db.php';
require_once 'layout.php';
require_once 'monitor.php';

// 14g: kopia index.php dla administratora - zapytanie BEZ klauzuli WHERE (wszystkie hosty wszystkich użytkowników)
$link = db_connect();
$result = mysqli_query($link, 'SELECT d.id, d.host, d.port, d.errors, d.failure, d.total_errors, u.username FROM domeny d JOIN users u ON u.id = d.user_id ORDER BY d.id');
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

page_start('Wszystkie monitorowane usługi (administrator)');
render_monitor($link, $rows, true);
page_end();
mysqli_close($link);
