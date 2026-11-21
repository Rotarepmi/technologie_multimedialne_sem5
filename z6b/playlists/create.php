<?php
// 15: zapis nowej playlisty w tabeli playlistname (domyślnie prywatna, 13d)
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_post_csrf();

$name = trim((string)($_POST['name'] ?? ''));
$public = !empty($_POST['public']) ? 1 : 0;

if ($name === '' || mb_strlen($name) > 100) {
  flash('danger', 'Podaj nazwę playlisty (maks. 100 znaków).');
  redirect('index.php');
}
$link = db_connect();
$ok = execute($link, 'INSERT INTO playlistname (idu, name, public) VALUES (?, ?, ?)', 'isi', current_user_id(), $name, $public) === 1;
$idpl = mysqli_insert_id($link);
mysqli_close($link);

if (!$ok) {
  flash('danger', 'Nie udało się utworzyć playlisty.');
  redirect('index.php');
}
flash('success', "Utworzono " . ($public ? 'publiczną' : 'prywatną') . " playlistę „{$name}”. Dodaj do niej filmy z listy filmów.");
redirect('play.php?idpl=' . $idpl);
