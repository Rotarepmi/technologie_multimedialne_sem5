<?php
// 8, 11: odbiór przesłanego filmu (upload.php z mySpotify). Plik trafia do
// films/<login> (katalog macierzysty), a dane z formularza do tabeli film.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';

// Plik większy niż post_max_size: PHP odrzuca całe żądanie i $_POST/$_FILES są puste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
  flash('danger', 'Przesyłane dane przekraczają limit serwera (' . human_size(upload_limit()) . ').');
  redirect('select.php');
}
require_post_csrf();

$title = trim((string)($_POST['title'] ?? ''));
$director = trim((string)($_POST['director'] ?? ''));
$idft = (int)($_POST['idft'] ?? 0);
$subtitles = trim((string)($_POST['subtitles'] ?? ''));
$file = $_FILES['fileToUpload'] ?? null;

function fail(string $msg): never
{
  $_SESSION['upload_old'] = array_intersect_key($_POST, array_flip(['title', 'director', 'idft', 'subtitles']));
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

if ($title === '' || $director === '' || mb_strlen($title) > 200 || mb_strlen($director) > 200) {
  fail('Podaj tytuł i reżysera (maks. 200 znaków).');
}
if (strlen($subtitles) > 65535) {
  fail('Napisy są za długie (maks. 64 KB).');
}
if (!$file || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
  fail('Błąd przesyłania - ' . ($errors[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'nieznany błąd') . '.');
}
// 8a, 12a: tylko pliki mp4 - rozszerzenie + typ wykryty z zawartości (kontener MPEG-4)
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
if (file_ext($file['name']) !== 'mp4' || !in_array($mime, ['video/mp4', 'application/mp4', 'video/x-m4v'], true)) {
  fail('Dozwolone są tylko pliki MP4.');
}

$link = db_connect();
if (!query_one($link, 'SELECT idft FROM filmtype WHERE idft = ?', 'i', $idft)) {
  fail('Wybierz gatunek z listy.');
}

$owner = $_SESSION['username'];
if (!ensure_home($owner)) {
  fail('Brak katalogu macierzystego na serwerze.');
}
// 7c: nazwa unikalna w obrębie katalogu użytkownika - istniejący plik nie jest nadpisywany ("nazwa (1).mp4")
$filename = unique_target(home_path($owner), safe_filename($file['name']));
if (!move_uploaded_file($file['tmp_name'], film_path($owner, $filename))) {
  fail('Nie udało się zapisać pliku.');
}

$idu = current_user_id();
$subtitlesOrNull = $subtitles === '' ? null : $subtitles;
if (execute($link, 'INSERT INTO film (title, director, idu, filename, subtitles, idft) VALUES (?, ?, ?, ?, ?, ?)',
    'ssissi', $title, $director, $idu, $filename, $subtitlesOrNull, $idft) !== 1) {
  unlink(film_path($owner, $filename));
  fail('Nie udało się zapisać filmu w bazie danych.');
}
$idf = mysqli_insert_id($link);
mysqli_close($link);

flash('success', "Dodano film „{$title}” ({$filename}).");
redirect('play.php?idf=' . $idf);
