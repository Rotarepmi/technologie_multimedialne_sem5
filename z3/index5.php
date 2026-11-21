<?php
// 8: lista plików z prawami w katalogu zadania
declare(strict_types=1);
require_once 'auth.php';
require_once 'layout.php';

page_start('ls -al - pliki z prawami');
print_command_output(exec_available('shell_exec') ? shell_exec('ls -al') : null, "shell_exec('ls -al')");
page_end();
