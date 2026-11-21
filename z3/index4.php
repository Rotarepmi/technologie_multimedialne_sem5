<?php
// 7: obciążenie serwera, RAM, liczba procesów - polecenie top w trybie wsadowym
declare(strict_types=1);
require_once 'auth.php';
require_once 'layout.php';

page_start('Top - obciążenie, RAM, procesy');
$top = [];
$error = 0;
if (exec_available('exec')) {
  exec('TERM=xterm top -n 1 -b -i 2>&1', $top, $error);
}
print_command_output(implode("\n", $top), 'top -n 1 -b -i');
if ($error) {
  echo '<p class="text-danger small">Kod błędu: ' . (int)$error . '</p>';
}
if (function_exists('sys_getloadavg') && ($load = sys_getloadavg())) {
  echo '<p class="small">sys_getloadavg(): ' . implode(', ', array_map(fn ($l) => round($l, 2), $load)) . '</p>';
}
page_end();
