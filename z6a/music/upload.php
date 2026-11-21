<?php
// 4, 7: odbiór przesłanego utworu (wg upload.php z poprzednich zadań). Plik trafia do
// songs/<login> (katalog macierzysty), a dane z formularza do tabeli song.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';

// Plik większy niż post_max_size: PHP odrzuca całe żądanie i $_POST/$_FILES są puste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
  flash('danger', 'Przesyłane dane przekraczają limit serwera (' . human_size(upload_limit()) . ').');
  redirect('select.php');
}
require_post_csrf();

$title = trim((string)($_POST['title'] ?? ''));
$musician = trim((string)($_POST['musician'] ?? ''));
$idmt = (int)($_POST['idmt'] ?? 0);
$lyrics = trim((string)($_POST['lyrics'] ?? ''));
$file = $_FILES['fileToUpload'] ?? null;

function fail(string $msg): never
{
  $_SESSION['upload_old'] = array_intersect_key($_POST, array_flip(['title', 'musician', 'idmt', 'lyrics']));
  flash('danger', $msg);
  redirect('select.php');
}

$errors = [
  UPLOAD_ERR_INI_SIZE => 'plik przekracza limit serwera (' . human_size(upload_limit()) . ')',
  UPLOAD_ERR_FORM_SIZE => 'plik przekracza limit formularza',
  UPLOAD_ERR_PARTIAL => 'plik został przesłany tylko częściowo',
  UPLOAD_ERR_NO_FILE => 'nie wybrano pliku',
  UPLOAD_ERR_NO_TMP_DIR => 'brak katalogu tymczasowego na serwerze',
  UPLOAD_ERR_CANT_WRITE => 'błąd zapisu na dysku serwera',
  UPLOAD_ERR_EXTENSION => 'przesyłanie zablokowane przez rozszerzenie PHP',
];

if ($title === '' || $musician === '' || mb_strlen($title) > 200 || mb_strlen($musician) > 200) {
  fail('Podaj tytuł i wykonawcę (maks. 200 znaków).');
}
if (!$file || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
  fail('Błąd przesyłania - ' . ($errors[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'nieznany błąd') . '.');
}
// 8a: tylko pliki mp3 - rozszerzenie + typ wykryty z zawartości (MP3 bez znaczników ID3 bywa rozpoznawany jako octet-stream)
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
if (file_ext($file['name']) !== 'mp3' || !in_array($mime, ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'application/octet-stream'], true)) {
  fail('Dozwolone są tylko pliki MP3.');
}

$link = db_connect();
if (!query_one($link, 'SELECT idmt FROM musictype WHERE idmt = ?', 'i', $idmt)) {
  fail('Wybierz gatunek z listy.');
}

$owner = $_SESSION['username'];
if (!ensure_home($owner)) {
  fail('Brak katalogu macierzystego na serwerze.');
}
// 3c: nazwa unikalna w obrębie katalogu użytkownika - istniejący plik nie jest nadpisywany ("nazwa (1).mp3")
$filename = unique_target(home_path($owner), safe_filename($file['name']));
if (!move_uploaded_file($file['tmp_name'], song_path($owner, $filename))) {
  fail('Nie udało się zapisać pliku.');
}

$idu = current_user_id();
$lyricsOrNull = $lyrics === '' ? null : $lyrics;
if (execute($link, 'INSERT INTO song (title, musician, idu, filename, lyrics, idmt) VALUES (?, ?, ?, ?, ?, ?)',
    'ssissi', $title, $musician, $idu, $filename, $lyricsOrNull, $idmt) !== 1) {
  unlink(song_path($owner, $filename));
  fail('Nie udało się zapisać utworu w bazie danych.');
}
$ids = mysqli_insert_id($link);
mysqli_close($link);

flash('success', "Dodano utwór „{$title}” ({$filename}).");
redirect('play.php?ids=' . $ids);
