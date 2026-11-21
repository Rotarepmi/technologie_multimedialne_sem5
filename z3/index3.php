<?php
// 6: nazwa użytkownika systemowego, na którym działa PHP
declare(strict_types=1);
require_once 'auth.php';
require_once 'layout.php';

page_start('Whoami - użytkownik hostingu');
print_command_output(exec_available('exec') ? exec('whoami') : null, "exec('whoami')");
echo '<p class="small text-muted">Dla porównania get_current_user() (właściciel pliku skryptu): <code>'
  . htmlspecialchars(get_current_user()) . '</code></p>';
page_end();
