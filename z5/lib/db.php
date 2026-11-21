<?php
declare(strict_types=1);

function db_connect(): mysqli
{
  $link = mysqli_connect(getenv('DB_HOST') ?: 'mysql92.mydevil.net', getenv('DB_USER') ?: 'm1127_z', getenv('DB_PASS') ?: 'zaq1@WSX', 'm1127_z5');
  if (!$link) {
    exit('Nie udało się połączyć z bazą danych.');
  }
  mysqli_set_charset($link, 'utf8mb4');
  return $link;
}

// Zapytania przygotowane (prepared statements) - pierwszy wiersz wyniku albo null
function query_one(mysqli $link, string $sql, string $types, mixed ...$params): ?array
{
  $stmt = mysqli_prepare($link, $sql);
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = $result ? mysqli_fetch_assoc($result) : null;
  mysqli_stmt_close($stmt);
  return $row ?: null;
}

// Zapytanie przygotowane bez wyniku (INSERT/UPDATE/DELETE)
function execute(mysqli $link, string $sql, string $types, mixed ...$params): void
{
  $stmt = mysqli_prepare($link, $sql);
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}
