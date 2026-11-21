<?php
declare(strict_types=1);
session_start();
session_destroy(); // niszczy wszystkie zmienne sesyjne
header('Location: login.php');
exit();
