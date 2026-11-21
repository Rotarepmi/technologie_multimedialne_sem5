<?php
// Punkt wejścia z6b - strona główna myNetflix jest w module video/ (niezalogowanych auth.php odeśle do auth/login.php)
declare(strict_types=1);
header('Location: video/index.php');
exit();
