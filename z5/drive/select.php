<?php
// 6i-j: formularz wyboru pliku (wg select.php z instrukcji) - plik trafia do bieżącego katalogu
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/drive.php';
require_once __DIR__ . '/../layout/layout.php';

$dir = current_dir($_GET);
$limit = upload_limit();

page_start('Prześlij pliki');
?>
<p class="text-muted">
	Katalog docelowy: <strong><i class="bi bi-folder2-open"></i> <?= htmlspecialchars($_SESSION['username'] . ($dir === '' ? '' : '/' . $dir)) ?></strong>
</p>

<form action="upload.php" method="post" enctype="multipart/form-data" class="bg-white border rounded p-4 upload-form">
	<?= csrf_field() ?>
	<input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
	<label for="fileToUpload" class="form-label">Wybierz plik(i) do przesłania:</label>
	<input type="file" class="form-control mb-2" name="fileToUpload[]" id="fileToUpload" multiple required>
	<div class="form-text mb-3">
		Dowolny typ pliku<?= $limit ? ', maksymalny rozmiar pojedynczego przesłania: ' . human_size($limit) : '' ?>.
		Plik o istniejącej nazwie zostanie zapisany z dopiskiem (1), (2)...
	</div>
	<div id="selectedFiles" class="small text-muted mb-3"></div>
	<button type="submit" name="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Prześlij</button>
	<a href="<?= htmlspecialchars(dir_url($dir)) ?>" class="btn btn-outline-secondary">Anuluj</a>
	<div class="progress mt-3 d-none" id="uploadProgress" role="progressbar" aria-label="Postęp przesyłania">
		<div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">Przesyłanie...</div>
	</div>
</form>
<script>
	initUploadForm(<?= $limit ?>);
</script>
<?php
page_end();
