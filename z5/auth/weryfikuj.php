<?php
// 6b: logowanie z rejestracją udanych i błędnych prób w tabeli goscieportalu.
// 6b-iv: po 3 kolejnych błędnych próbach konto jest blokowane na 1 minutę, zdarzenie trafia do break_ins,
// a przy następnym poprawnym logowaniu użytkownik widzi czerwone ostrzeżenie z datą i IP ostatniej błędnej próby.
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/drive.php';

const MAX_FAILED_LOGINS = 3;
const BLOCK_SECONDS = 60;

$user = trim((string)($_POST['user'] ?? ''));
$pass = (string)($_POST['pass'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$browser = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);

if ($user === '' || $pass === '') {
  header('Location: login.php?error=1');
  exit();
}

$link = db_connect();

function log_login(mysqli $link, string $event, string $username, string $ip, string $browser): void
{
  execute($link, 'INSERT INTO goscieportalu (ipaddress, event, username, browser) VALUES (?, ?, ?, ?)',
    'ssss', $ip, $event, mb_substr($username, 0, 20), $browser);
}

function finish(mysqli $link, string $url): never
{
  mysqli_close($link);
  header('Location: ' . $url);
  exit();
}

$rekord = query_one($link,
  'SELECT id, username, password, avatar, failed_logins,
          GREATEST(COALESCE(TIMESTAMPDIFF(SECOND, NOW(), blocked_until), 0), 0) AS block_left
   FROM users WHERE username = ?', 's', $user);

// Konto zablokowane - próba jest odnotowana jako błędna, ale hasło nie jest nawet sprawdzane
if ($rekord && (int)$rekord['block_left'] > 0) {
  log_login($link, 'login_fail', $user, $ip, $browser);
  finish($link, 'login.php?blocked=' . (int)$rekord['block_left']);
}

if (!$rekord || $rekord['password'] !== $pass) {
  log_login($link, 'login_fail', $user, $ip, $browser);
  if ($rekord) {
    $failed = (int)$rekord['failed_logins'] + 1;
    if ($failed >= MAX_FAILED_LOGINS) {
      execute($link, 'UPDATE users SET failed_logins = 0, blocked_until = NOW() + INTERVAL ' . BLOCK_SECONDS . ' SECOND WHERE id = ?', 'i', $rekord['id']);
      execute($link, 'INSERT INTO break_ins (ipaddress, username) VALUES (?, ?)', 'ss', $ip, $rekord['username']);
      finish($link, 'login.php?blocked=' . BLOCK_SECONDS);
    }
    execute($link, 'UPDATE users SET failed_logins = ? WHERE id = ?', 'ii', $failed, $rekord['id']);
  }
  finish($link, 'login.php?error=1');
}

// Poprawne logowanie. Czy od poprzedniego udanego logowania konto było blokowane (break_ins)?
// Jeśli tak - pokazujemy datę/godzinę i IP ostatniej błędnej próby.
$warning = query_one($link,
  "SELECT g.datetime, g.ipaddress AS ip FROM goscieportalu g
   WHERE g.username = ? AND g.event = 'login_fail'
     AND EXISTS (
       SELECT 1 FROM break_ins b WHERE b.username = ? AND b.datetime >= COALESCE(
         (SELECT MAX(datetime) FROM goscieportalu WHERE username = ? AND event = 'login_ok'), '1000-01-01'))
   ORDER BY g.id DESC LIMIT 1",
  'sss', $rekord['username'], $rekord['username'], $rekord['username']);

execute($link, 'UPDATE users SET failed_logins = 0, blocked_until = NULL WHERE id = ?', 'i', $rekord['id']);
log_login($link, 'login_ok', $rekord['username'], $ip, $browser);
mysqli_close($link);

// 6c: konta utworzone przed z5 (np. admin) dostają katalog macierzysty przy pierwszym logowaniu
if (!ensure_home($rekord['username'])) {
  header('Location: login.php?nohome=1');
  exit();
}

session_regenerate_id(true); // dane sesji (w tym flaga visit_logged) zostają
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = (int)$rekord['id'];
$_SESSION['username'] = $rekord['username'];
$_SESSION['is_admin'] = $rekord['username'] === 'admin';
$_SESSION['avatar'] = $rekord['avatar'];               // null = domyślna ikona
$_SESSION['home'] = home_path($rekord['username']);    // 6c-ii: ścieżka katalogu macierzystego
if ($warning) {
  $_SESSION['security_warning'] = $warning;
}
header('Location: ../drive/index.php');
exit();
