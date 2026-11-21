<?php
declare(strict_types=1);

// 11: geolokalizacja IP przez ipinfo.io (format JSON).
// Wyniki są zapamiętywane w sesji, żeby każde wyświetlenie tabeli nie odpytywało API dla każdego wiersza
// (darmowy limit ipinfo.io jest ograniczony). Zwraca obiekt z polami ip, city, region, country, loc
// albo null, gdy IP jest prywatne / API nie odpowiada.
function ip_details(string $ip): ?object
{
  if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    return null; // np. 127.0.0.1 lub 172.x w Dockerze - ipinfo.io nie zna lokalizacji adresów prywatnych
  }
  $cache = &$_SESSION['geo_cache'];
  if (isset($cache[$ip]) && $cache[$ip]['t'] > time() - 3600) {
    return $cache[$ip]['d'];
  }
  $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
  $json = @file_get_contents("http://ipinfo.io/{$ip}/geo", false, $ctx);
  $details = $json ? json_decode($json) : null;
  if (!is_object($details) || isset($details->error) || isset($details->bogon)) {
    $details = null;
  }
  $cache[$ip] = ['t' => time(), 'd' => $details];
  return $details;
}

// 13: link do Google Maps na podstawie współrzędnych "lat,lng" z ipinfo.io
function maps_link(?string $loc): string
{
  if (!$loc) {
    return '-';
  }
  return "<a href='https://www.google.pl/maps/place/" . htmlspecialchars($loc) . "' target='_blank' rel='noopener'>LINK</a>";
}

// 15: z surowego User-Agent zostawia tylko użyteczne dane (przeglądarka + wersja, system).
// get_browser() wymaga skonfigurowanej bazy browscap.ini w php.ini; jeśli jej nie ma,
// zwracamy null i wiersz jest parsowany w przeglądarce biblioteką UAParser.js.
function parse_browser(?string $ua): ?array
{
  static $available = null;
  $available ??= ini_get('browscap') !== false && ini_get('browscap') !== '';
  if (!$available || !$ua) {
    return null;
  }
  $b = @get_browser($ua, true);
  if (!$b) {
    return null;
  }
  return [
    'browser' => trim(($b['browser'] ?? '') . ' ' . ($b['version'] ?? '')),
    'os' => trim(($b['platform'] ?? '') . ' ' . (($b['device_type'] ?? '') !== 'unknown' ? ($b['device_type'] ?? '') : '')),
  ];
}

function yes_no(string|int|null $v): string
{
  return $v === null ? "-" : ((int)$v ? "tak" : "nie");
}
