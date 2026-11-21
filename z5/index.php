<?php
// Punkt wejścia z5 - właściwa strona dysku jest w module drive/ (niezalogowanych auth.php odeśle do auth/login.php)
declare(strict_types=1);
header('Location: drive/index.php');
exit();
