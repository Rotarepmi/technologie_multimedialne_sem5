<?php
declare(strict_types=1);

function db_connect(): mysqli
{
  $link = mysqli_connect(getenv('DB_HOST') ?: 'mysql92.mydevil.net', getenv('DB_USER') ?: 'm1127_z', getenv('DB_PASS') ?: 'zaq1@WSX', 'm1127_z3');
  if (!$link) {
    exit('Nie udało się połączyć z bazą danych.');
  }
  mysqli_set_charset($link, 'utf8');
  return $link;
}
