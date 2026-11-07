<?php
declare(strict_types=1);

function db_connect(): mysqli
{
  $link = mysqli_connect('mysql92.mydevil.net', 'm1127_z', 'zaq1@WSX', 'm1127_z2');
  if (!$link) {
    exit('Nie udało się połączyć z bazą danych.');
  }
  mysqli_set_charset($link, 'utf8');
  return $link;
}
