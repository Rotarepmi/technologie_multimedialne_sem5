<?php
declare(strict_types=1);

function db_connect(): mysqli
{
  $link = mysqli_connect(getenv('DB_HOST') ?: 'mysql92.mydevil.net', getenv('DB_USER') ?: 'm1127_z', getenv('DB_PASS') ?: 'zaq1@WSX', 'm1127_z6b');
  if (!$link) {
    exit('Nie udało się połączyć z bazą danych.');
  }
  mysqli_set_charset($link, 'utf8mb4');
  return $link;
}

// Zapytanie przygotowane (prepared statement); $types = '' oznacza zapytanie bez parametrów
function run_stmt(mysqli $link, string $sql, string $types, array $params): mysqli_stmt
{
  $stmt = mysqli_prepare($link, $sql);
  if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
  }
  mysqli_stmt_execute($stmt);
  return $stmt;
}

// Wszystkie wiersze wyniku
function query_all(mysqli $link, string $sql, string $types = '', mixed ...$params): array
{
  $stmt = run_stmt($link, $sql, $types, $params);
  $result = mysqli_stmt_get_result($stmt);
  $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
  mysqli_stmt_close($stmt);
  return $rows;
}

// Pierwszy wiersz wyniku albo null
function query_one(mysqli $link, string $sql, string $types = '', mixed ...$params): ?array
{
  return query_all($link, $sql, $types, ...$params)[0] ?? null;
}

// Zapytanie bez wyniku (INSERT/UPDATE/DELETE) - zwraca liczbę zmienionych wierszy (-1 = błąd)
function execute(mysqli $link, string $sql, string $types = '', mixed ...$params): int
{
  $stmt = run_stmt($link, $sql, $types, $params);
  $affected = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);
  return $affected;
}
