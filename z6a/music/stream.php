<?php
// 8, 12: wydawanie pliku MP3 dla odtwarzacza HTML5. Katalog songs/ jest zablokowany dla bezpośrednich
// żądań (.htaccess), więc plik dostaje tylko zalogowany użytkownik z dostępem do utworu (własny utwór,
// utwór z publicznej playlisty lub z jego playlisty). Obsługa nagłówka Range pozwala przewijać utwór
// (Safari bez niej w ogóle nie odtworzy pliku).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';

$link = db_connect();
$song = accessible_song($link, (int)($_GET['ids'] ?? 0), current_user_id());
mysqli_close($link);
session_write_close(); // nie blokuj sesji na czas przesyłania pliku (inne żądania czekałyby)

$path = $song ? song_path($song['owner'], $song['filename']) : '';
if ($path === '' || !is_file($path) || !is_readable($path)) {
  http_response_code(404);
  exit('Nie znaleziono pliku.');
}

$download = isset($_GET['dl']);
$name = $song['musician'] . ' - ' . $song['title'] . '.mp3';
$size = filesize($path);

// Nazwa pliku w nagłówku: wersja ASCII dla starych przeglądarek + pełna nazwa UTF-8 (RFC 6266)
$ascii = preg_replace('/[^\x20-\x7E]|["\\\\\/]/u', '_', $name);
header('Content-Type: audio/mpeg');
header(sprintf('Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s', $download ? 'attachment' : 'inline', $ascii, rawurlencode($name)));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
header('Accept-Ranges: bytes');

$start = 0;
$end = $size - 1;
if ($size > 0 && isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) && ($m[1] !== '' || $m[2] !== '')) {
  if ($m[1] === '') {                     // "bytes=-500" = ostatnie 500 bajtów
    $start = max(0, $size - (int)$m[2]);
  } else {
    $start = (int)$m[1];
    if ($m[2] !== '') {
      $end = min((int)$m[2], $size - 1);
    }
  }
  if ($start > $end) {
    http_response_code(416);
    header("Content-Range: bytes */{$size}");
    exit();
  }
  http_response_code(206);
  header("Content-Range: bytes {$start}-{$end}/{$size}");
}
header('Content-Length: ' . max(0, $end - $start + 1));

if ($_SERVER['REQUEST_METHOD'] === 'HEAD' || $size === 0) {
  exit();
}

while (ob_get_level() > 0) {
  ob_end_clean();
}
$fp = fopen($path, 'rb');
fseek($fp, $start);
$left = $end - $start + 1;
while ($left > 0 && !feof($fp) && !connection_aborted()) {
  $chunk = fread($fp, (int)min(65536, $left));
  if ($chunk === false) {
    break;
  }
  echo $chunk;
  flush();
  $left -= strlen($chunk);
}
fclose($fp);
