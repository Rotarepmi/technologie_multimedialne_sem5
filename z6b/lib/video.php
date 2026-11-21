<?php
declare(strict_types=1);
require_once __DIR__ . '/files.php';

// Wspólne funkcje myNetflix (na bazie lib/music.php z mySpotify). Pliki video leżą w films/<login>/<filename>
// (7c: osobny katalog macierzysty każdego użytkownika - nazwy plików muszą być unikalne tylko w obrębie
// jednego użytkownika, tak jak w mySpotify - 7d). Katalog films/ jest zablokowany dla bezpośrednich
// żądań (.htaccess), pliki wydaje video/stream.php.

define('APP_ROOT', dirname(__DIR__));            // katalog z6b
const FILMS_ROOT = APP_ROOT . '/films';
const AVATARS_DIR = APP_ROOT . '/avatars';
const LOGIN_PATTERN = '/^[A-Za-z0-9_-]{3,20}$/'; // login = nazwa katalogu macierzystego

function home_path(string $username): string
{
  return FILMS_ROOT . '/' . $username;
}

// Katalog macierzysty tworzony przy rejestracji (oraz przy logowaniu, jeśli konto powstało wcześniej, np. admin)
function ensure_home(string $username): bool
{
  $dir = home_path($username);
  return is_dir($dir) || mkdir($dir, 0775, true);
}

function film_path(string $owner, string $filename): string
{
  return home_path($owner) . '/' . $filename;
}

function current_user_id(): int
{
  return (int)$_SESSION['user_id'];
}

// Kolumny filmu wraz z gatunkiem i loginem właściciela (do SELECT ... FROM film f)
const FILM_COLUMNS = 'f.idf, f.title, f.director, f.datetime, f.idu, f.filename, f.subtitles, f.idft,
  ft.name AS genre, u.username AS owner';
const FILM_JOINS = 'JOIN filmtype ft ON ft.idft = f.idft JOIN users u ON u.id = f.idu';

// Film dostępny dla użytkownika: własny, umieszczony na dowolnej publicznej playliście
// albo na jednej z jego playlist (np. dodany wcześniej z cudzej publicznej playlisty, 17b)
function accessible_film(mysqli $link, int $idf, int $idu): ?array
{
  return query_one($link,
    'SELECT ' . FILM_COLUMNS . ' FROM film f ' . FILM_JOINS . '
     WHERE f.idf = ? AND (f.idu = ? OR EXISTS (
       SELECT 1 FROM playlistdatabase pd JOIN playlistname pn ON pn.idpl = pd.idpl
       WHERE pd.idf = f.idf AND (pn.public = TRUE OR pn.idu = ?)))',
    'iii', $idf, $idu, $idu);
}

// Playlista dostępna dla użytkownika: własna albo publiczna
function accessible_playlist(mysqli $link, int $idpl, int $idu): ?array
{
  return query_one($link,
    'SELECT pn.idpl, pn.idu, pn.name, pn.public, pn.datetime, u.username AS owner
     FROM playlistname pn JOIN users u ON u.id = pn.idu
     WHERE pn.idpl = ? AND (pn.public = TRUE OR pn.idu = ?)',
    'ii', $idpl, $idu);
}

function own_playlist(mysqli $link, int $idpl, int $idu): ?array
{
  return query_one($link, 'SELECT idpl, name, public FROM playlistname WHERE idpl = ? AND idu = ?', 'ii', $idpl, $idu);
}

function playlist_films(mysqli $link, int $idpl): array
{
  return query_all($link,
    'SELECT pd.idpldb, ' . FILM_COLUMNS . ' FROM playlistdatabase pd
     JOIN film f ON f.idf = pd.idf ' . FILM_JOINS . '
     WHERE pd.idpl = ? ORDER BY pd.idpldb',
    'i', $idpl);
}

function my_playlists(mysqli $link, int $idu): array
{
  return query_all($link, 'SELECT idpl, name, public FROM playlistname WHERE idu = ? ORDER BY name', 'i', $idu);
}

function genres(mysqli $link): array
{
  return query_all($link, 'SELECT idft, name FROM filmtype ORDER BY idft');
}

function stream_url(int $idf): string
{
  return '../video/stream.php?' . http_build_query(['idf' => $idf]);
}

function subtitles_url(int $idf): string
{
  return '../video/subtitles.php?' . http_build_query(['idf' => $idf]);
}

// Napisy w formacie SRT lub WebVTT można podpiąć pod odtwarzacz jako <track>; zwykły tekst jest tylko wyświetlany
function is_timed_subtitles(?string $text): bool
{
  return $text !== null && preg_match('/\d{1,2}:\d{2}(:\d{2})?[.,]\d{3}\s*-->\s*/', $text) === 1;
}

// SRT -> WebVTT (przeglądarki obsługują w <track> tylko WebVTT): nagłówek i kropka zamiast przecinka w czasie
function to_webvtt(string $text): string
{
  $text = str_replace(["\r\n", "\r"], "\n", trim(preg_replace('/^\xEF\xBB\xBF/', '', $text)));
  if (str_starts_with($text, 'WEBVTT')) {
    return $text . "\n";
  }
  $text = preg_replace('/(\d{1,2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $text);
  return "WEBVTT\n\n" . $text . "\n";
}

// Lista rozwijana "dodaj do playlisty" (17b) - wspólna dla listy filmów i odtwarzacza playlisty
function add_to_playlist_form(int $idf, array $playlists, string $back): string
{
  if (!$playlists) {
    return '';
  }
  $html = '<form method="post" action="../playlists/add.php" class="d-inline-flex gap-1 add-to-pl">'
    . csrf_field()
    . '<input type="hidden" name="idf" value="' . $idf . '">'
    . '<input type="hidden" name="back" value="' . htmlspecialchars($back) . '">'
    . '<select name="idpl" class="form-select form-select-sm" required aria-label="Playlista">'
    . '<option value="" selected disabled>Dodaj do playlisty…</option>';
  foreach ($playlists as $pl) {
    $html .= '<option value="' . (int)$pl['idpl'] . '">' . htmlspecialchars($pl['name']) . '</option>';
  }
  return $html . '</select><button type="submit" class="btn btn-sm btn-outline-danger" title="Dodaj do playlisty"><i class="bi bi-plus-lg"></i></button></form>';
}

// Powrót tylko na strony tej aplikacji (ochrona przed open redirect)
function safe_back(string $back, string $default): string
{
  return preg_match('#^\.\./(video|playlists)/[a-z_]+\.php(\?[^\s]*)?$#', $back) ? $back : $default;
}
