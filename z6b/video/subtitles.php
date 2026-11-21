<?php
// Napisy filmu (kolumna film.subtitles) jako plik WebVTT dla elementu <track> odtwarzacza HTML5.
// Napisy w formacie SRT są konwertowane do WebVTT; dostęp jak do samego filmu (accessible_film).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';

$link = db_connect();
$film = accessible_film($link, (int)($_GET['idf'] ?? 0), current_user_id());
mysqli_close($link);
session_write_close();

if (!$film || !is_timed_subtitles($film['subtitles'])) {
  http_response_code(404);
  exit();
}
header('Content-Type: text/vtt; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache');
echo to_webvtt($film['subtitles']);
