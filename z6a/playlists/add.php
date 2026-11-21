<?php
// 11b, 13b: dodanie utworu do własnej playlisty (playlistdatabase). Można dodać własny utwór
// albo cudzy utwór, do którego użytkownik ma dostęp (z publicznej playlisty innego użytkownika).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_post_csrf();

$idu = current_user_id();
$idpl = (int)($_POST['idpl'] ?? 0);
$back = safe_back((string)($_POST['back'] ?? ''), '../music/index.php');

$link = db_connect();
$pl = own_playlist($link, $idpl, $idu);
$song = accessible_song($link, (int)($_POST['ids'] ?? 0), $idu);
if (!$pl) {
  flash('danger', 'Wybierz jedną ze swoich playlist.');
} elseif (!$song) {
  flash('danger', 'Utwór nie istnieje lub nie masz do niego dostępu.');
} elseif (execute($link, 'INSERT IGNORE INTO playlistdatabase (idpl, ids) VALUES (?, ?)', 'ii', $idpl, $song['ids']) === 1) {
  flash('success', "Dodano „{$song['title']}” do playlisty „{$pl['name']}”.");
} else {
  flash('info', "Utwór „{$song['title']}” jest już na playliście „{$pl['name']}”.");
}
mysqli_close($link);
redirect($back);
