<?php
// Punkt wejścia z6a - strona główna mySpotify jest w module music/ (niezalogowanych auth.php odeśle do auth/login.php)
declare(strict_types=1);
header('Location: music/index.php');
exit();
