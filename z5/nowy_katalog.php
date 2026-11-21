<?php
// 6f: tworzenie podkatalogu (mkdir) wewnątrz katalogu macierzystego
declare(strict_types=1);
require_once 'auth.php';
require_once 'drive.php';
require_post_csrf();

$dir = current_dir($_POST);
$name = trim((string)($_POST['name'] ?? ''));

if (rel_depth($dir) >= MAX_DEPTH) {
  flash('warning', 'Osiągnięto maksymalną głębokość podkatalogów (' . MAX_DEPTH . ').');
} elseif (!valid_name($name) || mb_strlen($name) > 100) {
  flash('danger', 'Niedozwolona nazwa katalogu.');
} elseif (file_exists(abs_path(join_rel($dir, $name)))) {
  flash('warning', "Katalog lub plik „{$name}” już istnieje.");
} elseif (mkdir(abs_path(join_rel($dir, $name)), 0775)) {
  flash('success', "Utworzono katalog „{$name}”.");
} else {
  flash('danger', 'Nie udało się utworzyć katalogu.');
}
redirect(dir_url($dir));
