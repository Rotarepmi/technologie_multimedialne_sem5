<?php
declare(strict_types=1);

// Wspólne funkcje dysku sieciowego. Wszystkie operacje dyskowe odbywają się wewnątrz
// katalogu macierzystego użytkownika ($_SESSION['home'] = dysk/<login>), a ścieżki
// przekazywane w URL-ach są względne wobec niego i walidowane przed użyciem.

const DRIVE_ROOT = __DIR__ . '/dysk';
const MAX_DEPTH = 4;                          // 8b: maks. 4 poziomy podkatalogów (1 = tylko wersja podstawowa 6f-ii)
const LOGIN_PATTERN = '/^[A-Za-z0-9_-]{3,20}$/'; // 6c-i: tylko znaki dozwolone w nazwach katalogów

function home_path(string $username): string
{
  return DRIVE_ROOT . '/' . $username;
}

// 6c: katalog macierzysty tworzony przy rejestracji (oraz przy logowaniu, jeśli konto powstało wcześniej, np. admin)
function ensure_home(string $username): bool
{
  $dir = home_path($username);
  return is_dir($dir) || mkdir($dir, 0775, true);
}

// Pojedynczy element ścieżki: bez ukrytych plików (.htaccess!), "..", ukośników i znaków sterujących
function valid_name(string $name): bool
{
  return $name !== '' && strlen($name) <= 255 && $name[0] !== '.'
    && !preg_match('/[\/\\\\\x00-\x1F\x7F]/', $name);
}

// Normalizuje ścieżkę względną ('' = katalog macierzysty); null = ścieżka niedozwolona
function clean_rel(string $rel): ?string
{
  $rel = trim(str_replace('\\', '/', $rel), '/');
  if ($rel === '') {
    return '';
  }
  $parts = explode('/', $rel);
  if (count($parts) > MAX_DEPTH) {
    return null;
  }
  foreach ($parts as $p) {
    if (!valid_name($p)) {
      return null;
    }
  }
  return implode('/', $parts);
}

function rel_depth(string $rel): int
{
  return $rel === '' ? 0 : substr_count($rel, '/') + 1;
}

function abs_path(string $rel): string
{
  return $_SESSION['home'] . ($rel === '' ? '' : '/' . $rel);
}

// Katalog z parametru ?dir= (lub POST dir); nieistniejący/niedozwolony -> katalog macierzysty
function current_dir(array $src): string
{
  $rel = clean_rel((string)($src['dir'] ?? ''));
  return $rel !== null && is_dir(abs_path($rel)) ? $rel : '';
}

function parent_rel(string $rel): string
{
  $pos = strrpos($rel, '/');
  return $pos === false ? '' : substr($rel, 0, $pos);
}

function join_rel(string $dir, string $name): string
{
  return $dir === '' ? $name : $dir . '/' . $name;
}

// Lista plików i podkatalogów: najpierw katalogi, potem pliki, sortowanie naturalne bez rozróżniania wielkości liter
function list_dir(string $rel): array
{
  $abs = abs_path($rel);
  $items = [];
  foreach (scandir($abs) ?: [] as $name) {
    if (!valid_name($name)) {
      continue;
    }
    $path = $abs . '/' . $name;
    $isDir = is_dir($path);
    $items[] = [
      'name' => $name,
      'dir' => $isDir,
      'size' => $isDir ? null : filesize($path),
      'mtime' => filemtime($path),
      'count' => $isDir ? count(array_filter(scandir($path) ?: [], 'valid_name')) : null,
    ];
  }
  usort($items, fn($a, $b) => $b['dir'] <=> $a['dir'] ?:strnatcasecmp($a['name'], $b['name']));
  return $items;
}

// Usuwanie podkatalogu: najpierw jego zawartość (6g-iii), potem sam katalog
function remove_tree(string $path): bool
{
  if (is_dir($path) && !is_link($path)) {
    foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
      if (!remove_tree($path . '/' . $entry)) {
        return false;
      }
    }
    return rmdir($path);
  }
  return unlink($path);
}

// Typy plików, które przeglądarka potrafi wyświetlić/odtworzyć. Pozostałe pliki są zawsze pobierane.
// SVG i HTML celowo pominięte - wyświetlone w domenie portalu mogłyby wykonać skrypt (XSS).
const INLINE_TYPES = [
  'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
  'webp' => 'image/webp', 'bmp' => 'image/bmp', 'avif' => 'image/avif',
  'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg', 'mov' => 'video/quicktime',
  'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg', 'opus' => 'audio/ogg',
  'm4a' => 'audio/mp4', 'aac' => 'audio/aac', 'flac' => 'audio/flac',
  'pdf' => 'application/pdf', 'txt' => 'text/plain; charset=utf-8',
];

function file_ext(string $name): string
{
  return strtolower(pathinfo($name, PATHINFO_EXTENSION));
}

function inline_type(string $name): ?string
{
  return INLINE_TYPES[file_ext($name)] ?? null;
}

// 'image' | 'video' | 'audio' | 'other' - decyduje o miniaturze/odtwarzaczu w trybie podglądu
function file_kind(string $name): string
{
  $type = inline_type($name) ?? '';
  foreach (['image', 'video', 'audio'] as $kind) {
    if (str_starts_with($type, $kind . '/')) {
      return $kind;
    }
  }
  return 'other';
}

function file_icon(string $name): string
{
  return match (file_kind($name)) {
    'image' => 'bi-file-earmark-image text-success',
    'video' => 'bi-file-earmark-play text-danger',
    'audio' => 'bi-file-earmark-music text-warning',
    default => match (file_ext($name)) {
      'pdf' => 'bi-file-earmark-pdf text-danger',
      'doc', 'docx', 'odt', 'rtf' => 'bi-file-earmark-word text-primary',
      'xls', 'xlsx', 'ods', 'csv' => 'bi-file-earmark-spreadsheet text-success',
      'ppt', 'pptx', 'odp' => 'bi-file-earmark-slides text-warning',
      'zip', 'rar', '7z', 'gz', 'tar' => 'bi-file-earmark-zip text-secondary',
      'txt', 'md' => 'bi-file-earmark-text text-secondary',
      default => 'bi-file-earmark text-secondary',
    },
  };
}

function human_size(?int $bytes): string
{
  if ($bytes === null) {
    return '';
  }
  $units = ['B', 'KB', 'MB', 'GB'];
  $i = 0;
  $size = (float)$bytes;
  while ($size >= 1024 && $i < count($units) - 1) {
    $size /= 1024;
    $i++;
  }
  return ($i === 0 ? (string)$bytes : number_format($size, 1, ',', ' ')) . ' ' . $units[$i];
}

function file_url(string $rel, bool $download = false): string
{
  return 'plik.php?' . http_build_query(['f' => $rel] + ($download ? ['dl' => 1] : []));
}

function dir_url(string $rel): string
{
  return $rel === '' ? 'index.php' : 'index.php?' . http_build_query(['dir' => $rel]);
}

// Limit uploadu z php.ini w bajtach (min z upload_max_filesize i post_max_size)
function ini_bytes(string $key): int
{
  $v = trim((string)ini_get($key));
  $n = (int)$v;
  return match (strtolower(substr($v, -1))) {
    'g' => $n * 1024 ** 3,
    'm' => $n * 1024 ** 2,
    'k' => $n * 1024,
    default => $n,
  };
}

function upload_limit(): int
{
  $limits = array_filter([ini_bytes('upload_max_filesize'), ini_bytes('post_max_size')]);
  return $limits ? min($limits) : 0;
}
