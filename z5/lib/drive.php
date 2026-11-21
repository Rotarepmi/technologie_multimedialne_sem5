<?php
declare(strict_types=1);
require_once __DIR__ . '/files.php';

// Wspólne funkcje dysku sieciowego. Wszystkie operacje dyskowe odbywają się wewnątrz
// katalogu macierzystego użytkownika ($_SESSION['home'] = dysk/<login>), a ścieżki
// przekazywane w URL-ach są względne wobec niego i walidowane przed użyciem.

define('APP_ROOT', dirname(__DIR__));            // katalog z5
const DRIVE_ROOT = APP_ROOT . '/dysk';
const AVATARS_DIR = APP_ROOT . '/avatars';
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

function file_url(string $rel, bool $download = false): string
{
  return 'plik.php?' . http_build_query(['f' => $rel] + ($download ? ['dl' => 1] : []));
}

function dir_url(string $rel): string
{
  return $rel === '' ? 'index.php' : 'index.php?' . http_build_query(['dir' => $rel]);
}
