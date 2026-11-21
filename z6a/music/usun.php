<?php
// Usuwanie własnego utworu: plik z songs/<login> + wiersz w song (ON DELETE CASCADE usuwa go też z playlist)
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_post_csrf();

$idu = current_user_id();
$link = db_connect();
$song = query_one($link, 'SELECT ids, title, filename FROM song WHERE ids = ? AND idu = ?', 'ii', (int)($_POST['ids'] ?? 0), $idu);
if (!$song) {
  flash('danger', 'Nie znaleziono utworu do usunięcia (możesz usuwać tylko własne utwory).');
} elseif (execute($link, 'DELETE FROM song WHERE ids = ? AND idu = ?', 'ii', $song['ids'], $idu) === 1) {
  $path = song_path($_SESSION['username'], $song['filename']);
  if (is_file($path)) {
    unlink($path);
  }
  flash('success', "Usunięto utwór „{$song['title']}”.");
} else {
  flash('danger', 'Nie udało się usunąć utworu.');
}
mysqli_close($link);
redirect('index.php');
