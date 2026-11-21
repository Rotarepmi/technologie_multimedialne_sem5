<?php
declare(strict_types=1);
require_once __DIR__ . '/files.php';

// Wspólne funkcje mySpotify. Pliki audio leżą w songs/<login>/<filename> (3c: osobny katalog
// macierzysty każdego użytkownika - nazwy plików muszą być unikalne tylko w obrębie jednego użytkownika).
// Katalog songs/ jest zablokowany dla bezpośrednich żądań (.htaccess), pliki wydaje music/stream.php.

define('APP_ROOT', dirname(__DIR__));            // katalog z6a
const SONGS_ROOT = APP_ROOT . '/songs';
const AVATARS_DIR = APP_ROOT . '/avatars';
const LOGIN_PATTERN = '/^[A-Za-z0-9_-]{3,20}$/'; // login = nazwa katalogu macierzystego

function home_path(string $username): string
{
  return SONGS_ROOT . '/' . $username;
}

// Katalog macierzysty tworzony przy rejestracji (oraz przy logowaniu, jeśli konto powstało wcześniej, np. admin)
function ensure_home(string $username): bool
{
  $dir = home_path($username);
  return is_dir($dir) || mkdir($dir, 0775, true);
}

function song_path(string $owner, string $filename): string
{
  return home_path($owner) . '/' . $filename;
}

function current_user_id(): int
{
  return (int)$_SESSION['user_id'];
}

// Kolumny utworu wraz z gatunkiem i loginem właściciela (do SELECT ... FROM song s)
const SONG_COLUMNS = 's.ids, s.title, s.musician, s.datetime, s.idu, s.filename, s.lyrics, s.idmt,
  mt.name AS genre, u.username AS owner';
const SONG_JOINS = 'JOIN musictype mt ON mt.idmt = s.idmt JOIN users u ON u.id = s.idu';

// Utwór dostępny dla użytkownika: własny, umieszczony na dowolnej publicznej playliście
// albo na jednej z jego playlist (np. dodany wcześniej z cudzej publicznej playlisty, 13b)
function accessible_song(mysqli $link, int $ids, int $idu): ?array
{
  return query_one($link,
    'SELECT ' . SONG_COLUMNS . ' FROM song s ' . SONG_JOINS . '
     WHERE s.ids = ? AND (s.idu = ? OR EXISTS (
       SELECT 1 FROM playlistdatabase pd JOIN playlistname pn ON pn.idpl = pd.idpl
       WHERE pd.ids = s.ids AND (pn.public = TRUE OR pn.idu = ?)))',
    'iii', $ids, $idu, $idu);
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

function playlist_songs(mysqli $link, int $idpl): array
{
  return query_all($link,
    'SELECT pd.idpldb, ' . SONG_COLUMNS . ' FROM playlistdatabase pd
     JOIN song s ON s.ids = pd.ids ' . SONG_JOINS . '
     WHERE pd.idpl = ? ORDER BY pd.idpldb',
    'i', $idpl);
}

function my_playlists(mysqli $link, int $idu): array
{
  return query_all($link, 'SELECT idpl, name, public FROM playlistname WHERE idu = ? ORDER BY name', 'i', $idu);
}

function genres(mysqli $link): array
{
  return query_all($link, 'SELECT idmt, name FROM musictype ORDER BY idmt');
}

function stream_url(int $ids): string
{
  return '../music/stream.php?' . http_build_query(['ids' => $ids]);
}

// Lista rozwijana "dodaj do playlisty" (11b, 13b) - wspólna dla listy utworów i odtwarzacza playlisty
function add_to_playlist_form(int $ids, array $playlists, string $back): string
{
  if (!$playlists) {
    return '';
  }
  $html = '<form method="post" action="../playlists/add.php" class="d-inline-flex gap-1 add-to-pl">'
    . csrf_field()
    . '<input type="hidden" name="ids" value="' . $ids . '">'
    . '<input type="hidden" name="back" value="' . htmlspecialchars($back) . '">'
    . '<select name="idpl" class="form-select form-select-sm" required aria-label="Playlista">'
    . '<option value="" selected disabled>Dodaj do playlisty…</option>';
  foreach ($playlists as $pl) {
    $html .= '<option value="' . (int)$pl['idpl'] . '">' . htmlspecialchars($pl['name']) . '</option>';
  }
  return $html . '</select><button type="submit" class="btn btn-sm btn-outline-primary" title="Dodaj do playlisty"><i class="bi bi-plus-lg"></i></button></form>';
}

// Powrót tylko na strony tej aplikacji (ochrona przed open redirect)
function safe_back(string $back, string $default): string
{
  return preg_match('#^\.\./(music|playlists)/[a-z_]+\.php(\?[^\s]*)?$#', $back) ? $back : $default;
}
