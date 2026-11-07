<?php
declare(strict_types=1);
$user = trim($_POST['user'] ?? '');
$pass = $_POST['pass'] ?? '';
$pass_confirm = $_POST['pass_confirm'] ?? '';

function fail(string $msg): void
{
  header('Location: rejestruj.php?error=' . urlencode($msg));
  exit();
}

if ($user === '' || $pass === '' || $pass_confirm === '') {
  fail('Wypełnij wszystkie pola.');
}
if ($pass !== $pass_confirm) {
  fail('Hasła nie są takie same.');
}

// Avatar jest opcjonalny - brak pliku = NULL w bazie i domyślna ikona w navbarze
$avatar = null;
$file = $_FILES['avatar'] ?? null;
if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
  $types = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
  $info = $file['error'] === UPLOAD_ERR_OK ? getimagesize($file['tmp_name']) : false;
  if (!$info || !isset($types[$info['mime']]) || $file['size'] > 2 * 1024 * 1024) {
    fail('Avatar musi być obrazkiem PNG/JPG/GIF/WEBP do 2 MB.');
  }
  if (!is_dir('avatars')) {
    mkdir('avatars', 0755);
  }
  $avatar = 'avatars/' . bin2hex(random_bytes(8)) . '.' . $types[$info['mime']];
  if (!move_uploaded_file($file['tmp_name'], $avatar)) {
    fail('Nie udało się zapisać avatara.');
  }
}

require_once 'db.php';
$link = db_connect();
$stmt = mysqli_prepare($link, 'INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sss', $user, $pass, $avatar);
$registered = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

if (!$registered) {
  if ($avatar) {
    unlink($avatar);
  }
  fail('Nie udało się zarejestrować użytkownika (login może być zajęty).');
}

header('Location: login.php?registered=1');
exit();
