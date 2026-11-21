<?php
// 8, 11: formularz przesyłania filmu (select.php z mySpotify) rozszerzony o dane do tabeli film:
// tytuł, reżyser, gatunek (lista <select> z tabeli filmtype) i opcjonalne napisy.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_once __DIR__ . '/../layout/layout.php';

$link = db_connect();
$genres = genres($link);
mysqli_close($link);
$limit = upload_limit();
$old = $_SESSION['upload_old'] ?? []; // dane formularza po nieudanym przesłaniu
unset($_SESSION['upload_old']);

page_start('Dodaj film');
?>
<p class="text-muted">
	Plik trafi do katalogu <strong><i class="bi bi-folder2-open"></i> films/<?= htmlspecialchars($_SESSION['username']) ?></strong>.
</p>

<form action="upload.php" method="post" enctype="multipart/form-data" class="bg-white border rounded p-4 upload-form needs-validation" novalidate>
	<?= csrf_field() ?>
	<div class="mb-3">
		<label for="fileToUpload" class="form-label">Plik MP4</label>
		<input type="file" class="form-control" name="fileToUpload" id="fileToUpload" accept=".mp4,video/mp4" required>
		<div class="form-text">
			Tylko pliki MP4<?= $limit ? ', maks. ' . human_size($limit) : '' ?>.
			Nazwa w formacie „Reżyser - Tytuł.mp4” uzupełni pola poniżej.
		</div>
		<div class="invalid-feedback">Wybierz plik MP4.</div>
	</div>
	<video id="uploadPreview" class="upload-preview d-none mb-3" controls muted playsinline></video>
	<div class="row g-3 mb-3">
		<div class="col-md-6">
			<label for="title" class="form-label">Tytuł</label>
			<input type="text" class="form-control" id="title" name="title" maxlength="200" required value="<?= htmlspecialchars($old['title'] ?? '') ?>">
			<div class="invalid-feedback">Podaj tytuł filmu.</div>
		</div>
		<div class="col-md-6">
			<label for="director" class="form-label">Reżyser</label>
			<input type="text" class="form-control" id="director" name="director" maxlength="200" required placeholder="Imię i nazwisko" value="<?= htmlspecialchars($old['director'] ?? '') ?>">
			<div class="invalid-feedback">Podaj imię i nazwisko reżysera.</div>
		</div>
		<div class="col-md-6">
			<label for="idft" class="form-label">Gatunek</label>
			<select class="form-select" id="idft" name="idft" required>
				<option value="" disabled<?= empty($old['idft']) ? ' selected' : '' ?>>Wybierz gatunek…</option>
				<?php foreach ($genres as $g): ?>
					<option value="<?= (int)$g['idft'] ?>"<?= (int)($old['idft'] ?? 0) === (int)$g['idft'] ? ' selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
				<?php endforeach; ?>
			</select>
			<div class="invalid-feedback">Wybierz gatunek.</div>
		</div>
	</div>
	<div class="mb-3">
		<label for="subtitles" class="form-label">Napisy <span class="text-muted">(opcjonalnie)</span></label>
		<input type="file" class="form-control form-control-sm mb-2" id="subtitlesFile" accept=".srt,.vtt,.txt,text/plain,text/vtt" aria-label="Wczytaj napisy z pliku">
		<textarea class="form-control font-monospace small" id="subtitles" name="subtitles" rows="6"
			placeholder="Wklej tekst lub wczytaj plik .srt / .vtt - napisy z kodami czasowymi pojawią się w odtwarzaczu"><?= htmlspecialchars($old['subtitles'] ?? '') ?></textarea>
	</div>
	<button type="submit" name="submit" class="btn btn-danger"><i class="bi bi-cloud-upload"></i> Prześlij</button>
	<a href="index.php" class="btn btn-outline-secondary">Anuluj</a>
	<div class="progress mt-3 d-none" id="uploadProgress" role="progressbar" aria-label="Postęp przesyłania">
		<div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%">Przesyłanie...</div>
	</div>
</form>
<script>
	initFilmUploadForm(<?= $limit ?>);
</script>
<?php
page_end();
