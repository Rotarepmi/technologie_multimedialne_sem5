<?php
// 6k: wyświetlanie/odtwarzanie i pobieranie plików z katalogu macierzystego.
// Katalog dysk/ jest zablokowany dla bezpośrednich żądań (.htaccess), więc pliki są dostępne
// wyłącznie przez ten skrypt - tylko dla zalogowanego właściciela. Obsługa nagłówka Range
// pozwala przewijać filmy i muzykę (Safari bez niej w ogóle nie odtworzy pliku mp4).
declare(strict_types=1);
require_once 'auth.php';
require_once 'drive.php';

$rel = clean_rel((string)($_GET['f'] ?? ''));
$path = $rel !== null && $rel !== '' ? abs_path($rel) : '';
if ($path === '' || !is_file($path) || !is_readable($path)) {
  http_response_code(404);
  exit('Nie znaleziono pliku.');
}
session_write_close(); // nie blokuj sesji na czas przesyłania dużego pliku (inne żądania czekałyby)

$name = basename($rel);
$type = inline_type($name);
$download = isset($_GET['dl']) || $type === null;
$size = filesize($path);

// Nazwa pliku w nagłówku: wersja ASCII dla starych przeglądarek + pełna nazwa UTF-8 (RFC 6266)
$ascii = preg_replace('/[^\x20-\x7E]|["\\\\]/u', '_', $name);
header('Content-Type: ' . ($download ? 'application/octet-stream' : $type));
header(sprintf('Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s', $download ? 'attachment' : 'inline', $ascii, rawurlencode($name)));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
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

// Wysyłanie surowych bajtów kawałkami - bez buforowania i bez żadnych dodatkowych znaków (np. BOM, spacji),
// które uszkodziłyby plik binarny (pdf, docx, mp4...)
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
