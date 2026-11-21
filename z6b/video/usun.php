<?php
// Usuwanie własnego filmu: plik z films/<login> + wiersz w film (ON DELETE CASCADE usuwa go też z playlist)
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_post_csrf();

$idu = current_user_id();
$link = db_connect();
$film = query_one($link, 'SELECT idf, title, filename FROM film WHERE idf = ? AND idu = ?', 'ii', (int)($_POST['idf'] ?? 0), $idu);
if (!$film) {
  flash('danger', 'Nie znaleziono filmu do usunięcia (możesz usuwać tylko własne filmy).');
} elseif (execute($link, 'DELETE FROM film WHERE idf = ? AND idu = ?', 'ii', $film['idf'], $idu) === 1) {
  $path = film_path($_SESSION['username'], $film['filename']);
  if (is_file($path)) {
    unlink($path);
  }
  flash('success', "Usunięto film „{$film['title']}”.");
} else {
  flash('danger', 'Nie udało się usunąć filmu.');
}
mysqli_close($link);
redirect('index.php');
