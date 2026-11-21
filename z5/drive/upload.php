<?php
// 6i-j: odbiór przesłanych plików (wg upload.php z instrukcji). $target_dir = katalog macierzysty
// zalogowanego użytkownika (lub jego podkatalog). Bez ograniczeń typu i rozmiaru (poza limitem PHP).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/drive.php';

// Plik większy niż post_max_size: PHP odrzuca całe żądanie i $_POST/$_FILES są puste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
  flash('danger', 'Przesyłane dane przekraczają limit serwera (' . human_size(upload_limit()) . ').');
  redirect('index.php');
}
require_post_csrf();

$dir = current_dir($_POST);
$target_dir = abs_path($dir);

$errors = [
  UPLOAD_ERR_INI_SIZE => 'plik przekracza limit serwera (' . human_size(upload_limit()) . ')',
  UPLOAD_ERR_FORM_SIZE => 'plik przekracza limit formularza',
  UPLOAD_ERR_PARTIAL => 'plik został przesłany tylko częściowo',
  UPLOAD_ERR_NO_FILE => 'nie wybrano pliku',
  UPLOAD_ERR_NO_TMP_DIR => 'brak katalogu tymczasowego na serwerze',
  UPLOAD_ERR_CANT_WRITE => 'błąd zapisu na dysku serwera',
  UPLOAD_ERR_EXTENSION => 'przesyłanie zablokowane przez rozszerzenie PHP',
];

$files = $_FILES['fileToUpload'] ?? null;
$ok = [];
$failed = [];
if ($files && is_array($files['name'])) {
  foreach ($files['name'] as $i => $original) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
      $failed[] = ($original !== '' ? $original . ': ' : '') . ($errors[$files['error'][$i]] ?? 'nieznany błąd');
      continue;
    }
    $target_file = unique_target($target_dir, safe_filename($original));
    if (move_uploaded_file($files['tmp_name'][$i], $target_dir . '/' . $target_file)) {
      $ok[] = $target_file;
    } else {
      $failed[] = $original . ': błąd zapisu pliku';
    }
  }
}

if ($failed) {
  flash('danger', ($ok ? 'Przesłano: ' . implode(', ', $ok) . '. ' : '') . 'Błąd przesyłania - ' . implode('; ', $failed) . '.');
} elseif ($ok) {
  flash('success', (count($ok) === 1 ? 'Przesłano plik: ' : 'Przesłano pliki: ') . implode(', ', $ok) . '.');
} else {
  flash('warning', 'Nie wybrano żadnego pliku.');
}
redirect(dir_url($dir));
