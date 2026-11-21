<?php
declare(strict_types=1);

// Funkcje dotyczące pojedynczych plików: typy MIME, ikony, rozmiary, limity i nazwy przesyłanych plików.
// Nie zależą od sesji ani od katalogu macierzystego.

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

// Nazwa z przeglądarki: tylko część po ostatnim ukośniku (basename), bez znaków sterujących i kropki na początku
function safe_filename(string $name): string
{
  $name = basename(str_replace('\\', '/', $name));
  $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);
  $name = ltrim($name, '.');
  if (strlen($name) > 200) {
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $name = mb_strcut($name, 0, 190) . ($ext !== '' ? '.' . mb_strcut($ext, 0, 9) : '');
  }
  return $name !== '' ? $name : 'plik';
}

// Istniejący plik nie jest nadpisywany - "nazwa (1).ext", "nazwa (2).ext", ...
function unique_target(string $dir, string $name): string
{
  $base = pathinfo($name, PATHINFO_FILENAME);
  $ext = pathinfo($name, PATHINFO_EXTENSION);
  $candidate = $name;
  for ($i = 1; file_exists($dir . '/' . $candidate); $i++) {
    $candidate = $base . " ({$i})" . ($ext !== '' ? '.' . $ext : '');
  }
  return $candidate;
}
