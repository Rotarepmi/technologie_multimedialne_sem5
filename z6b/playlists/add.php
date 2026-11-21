<?php
// 17b: dodanie filmu do własnej playlisty (playlistdatabase). Można dodać własny film
// albo cudzy film, do którego użytkownik ma dostęp (z publicznej playlisty innego użytkownika).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_post_csrf();

$idu = current_user_id();
$idpl = (int)($_POST['idpl'] ?? 0);
$back = safe_back((string)($_POST['back'] ?? ''), '../video/index.php');

$link = db_connect();
$pl = own_playlist($link, $idpl, $idu);
$film = accessible_film($link, (int)($_POST['idf'] ?? 0), $idu);
if (!$pl) {
  flash('danger', 'Wybierz jedną ze swoich playlist.');
} elseif (!$film) {
  flash('danger', 'Film nie istnieje lub nie masz do niego dostępu.');
} elseif (execute($link, 'INSERT IGNORE INTO playlistdatabase (idpl, idf) VALUES (?, ?)', 'ii', $idpl, $film['idf']) === 1) {
  flash('success', "Dodano „{$film['title']}” do playlisty „{$pl['name']}”.");
} else {
  flash('info', "Film „{$film['title']}” jest już na playliście „{$pl['name']}”.");
}
mysqli_close($link);
redirect($back);
