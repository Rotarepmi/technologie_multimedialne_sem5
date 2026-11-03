<?php
$user = trim($_POST['user'] ?? '');
$pass = $_POST['pass'] ?? '';
$pass_confirm = $_POST['pass_confirm'] ?? '';

if ($user === '' || $pass === '' || $pass_confirm === '') {
  exit('Wypełnij wszystkie pola.');
}

if ($pass !== $pass_confirm) {
  exit('Hasła nie są takie same.');
}

$link = mysqli_connect('mysql92.mydevil.net', 'm1127_z1', 'zaq1@WSX', 'm1127_z1');
if (!$link) {
  exit('Nie udało się połączyć z bazą danych.');
}

mysqli_set_charset($link, 'utf8');
$stmt = mysqli_prepare($link, 'INSERT INTO users (username, password) VALUES (?, ?)');
if (!$stmt) {
  mysqli_close($link);
  exit('Nie udało się zarejestrować użytkownika.');
}

mysqli_stmt_bind_param($stmt, 'ss', $user, $pass);
$registered = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

if (!$registered) {
  exit('Nie udało się zarejestrować użytkownika.');
}

header('Location: index3.php');
exit();
?>