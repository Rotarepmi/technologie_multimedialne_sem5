<?php
// 4, 7: formularz przesyłania utworu (wg select.php z poprzednich zadań) rozszerzony o dane do tabeli song:
// tytuł, wykonawca, gatunek (lista <select> z tabeli musictype) i opcjonalny tekst piosenki.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_once __DIR__ . '/../layout/layout.php';

$link = db_connect();
$genres = genres($link);
mysqli_close($link);
$limit = upload_limit();
$old = $_SESSION['upload_old'] ?? []; // dane formularza po nieudanym przesłaniu
unset($_SESSION['upload_old']);

page_start('Dodaj utwór');
?>
<p class="text-muted">
	Plik trafi do katalogu <strong><i class="bi bi-folder2-open"></i> songs/<?= htmlspecialchars($_SESSION['username']) ?></strong>.
</p>

<form action="upload.php" method="post" enctype="multipart/form-data" class="bg-white border rounded p-4 upload-form needs-validation" novalidate>
	<?= csrf_field() ?>
	<div class="mb-3">
		<label for="fileToUpload" class="form-label">Plik MP3</label>
		<input type="file" class="form-control" name="fileToUpload" id="fileToUpload" accept=".mp3,audio/mpeg" required>
		<div class="form-text">
			Tylko pliki MP3<?= $limit ? ', maks. ' . human_size($limit) : '' ?>.
			Nazwa w formacie „Wykonawca - Tytuł.mp3” uzupełni pola poniżej.
		</div>
		<div class="invalid-feedback">Wybierz plik MP3.</div>
	</div>
	<div class="row g-3 mb-3">
		<div class="col-md-6">
			<label for="title" class="form-label">Tytuł</label>
			<input type="text" class="form-control" id="title" name="title" maxlength="200" required value="<?= htmlspecialchars($old['title'] ?? '') ?>">
			<div class="invalid-feedback">Podaj tytuł utworu.</div>
		</div>
		<div class="col-md-6">
			<label for="musician" class="form-label">Wykonawca</label>
			<input type="text" class="form-control" id="musician" name="musician" maxlength="200" required value="<?= htmlspecialchars($old['musician'] ?? '') ?>">
			<div class="invalid-feedback">Podaj wykonawcę.</div>
		</div>
		<div class="col-md-6">
			<label for="idmt" class="form-label">Gatunek</label>
			<select class="form-select" id="idmt" name="idmt" required>
				<option value="" disabled<?= empty($old['idmt']) ? ' selected' : '' ?>>Wybierz gatunek…</option>
				<?php foreach ($genres as $g): ?>
					<option value="<?= (int)$g['idmt'] ?>"<?= (int)($old['idmt'] ?? 0) === (int)$g['idmt'] ? ' selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
				<?php endforeach; ?>
			</select>
			<div class="invalid-feedback">Wybierz gatunek.</div>
		</div>
	</div>
	<div class="mb-3">
		<label for="lyrics" class="form-label">Tekst piosenki <span class="text-muted">(opcjonalnie)</span></label>
		<textarea class="form-control" id="lyrics" name="lyrics" rows="6"><?= htmlspecialchars($old['lyrics'] ?? '') ?></textarea>
	</div>
	<button type="submit" name="submit" class="btn btn-success"><i class="bi bi-cloud-upload"></i> Prześlij</button>
	<a href="index.php" class="btn btn-outline-secondary">Anuluj</a>
	<div class="progress mt-3 d-none" id="uploadProgress" role="progressbar" aria-label="Postęp przesyłania">
		<div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%">Przesyłanie...</div>
	</div>
</form>
<script>
	initSongUploadForm(<?= $limit ?>);
</script>
<?php
page_end();
