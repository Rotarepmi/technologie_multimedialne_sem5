<?php
// 6g: usuwanie pliku (unlink) lub podkatalogu (najpierw jego zawartość, potem rmdir)
declare(strict_types=1);
require_once 'auth.php';
require_once 'drive.php';
require_post_csrf();

$rel = clean_rel((string)($_POST['f'] ?? ''));
if ($rel === null || $rel === '' || !file_exists(abs_path($rel))) {
  flash('danger', 'Nie znaleziono pliku do usunięcia.');
  redirect('index.php');
}

$name = basename($rel);
$isDir = is_dir(abs_path($rel));
if (remove_tree(abs_path($rel))) {
  flash('success', ($isDir ? 'Usunięto katalog' : 'Usunięto plik') . " „{$name}”.");
} else {
  flash('danger', "Nie udało się usunąć „{$name}”.");
}
redirect(dir_url(parent_rel($rel)));
