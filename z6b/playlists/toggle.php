<?php
// 13d: zmiana widoczności własnej playlisty (prywatna <-> publiczna)
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_post_csrf();

$idpl = (int)($_POST['idpl'] ?? 0);
$link = db_connect();
$pl = own_playlist($link, $idpl, current_user_id());
if ($pl) {
  execute($link, 'UPDATE playlistname SET public = NOT public WHERE idpl = ?', 'i', $idpl);
  flash('success', "Playlista „{$pl['name']}” jest teraz " . ($pl['public'] ? 'prywatna.' : 'publiczna.'));
} else {
  flash('danger', 'Nie znaleziono playlisty.');
}
mysqli_close($link);
redirect(safe_back((string)($_POST['back'] ?? ''), 'index.php'));
