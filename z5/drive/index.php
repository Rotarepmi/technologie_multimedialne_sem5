<?php
// 6d-h, 6k, 8a: lista plików i podkatalogów bieżącego katalogu (domyślnie katalogu macierzystego).
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/drive.php';
require_once __DIR__ . '/../layout/layout.php';

// 8a: tryb wyświetlania zapamiętany w sesji - lista nazw albo nazwy + miniatury/odtwarzacze
if (in_array($_GET['view'] ?? '', ['list', 'preview'], true)) {
  $_SESSION['view'] = $_GET['view'];
}
$view = $_SESSION['view'] ?? 'preview';

$dir = current_dir($_GET);
$depth = rel_depth($dir);
$items = list_dir($dir);
$totalSize = array_sum(array_column($items, 'size'));

page_start('Mój dysk');
?>
<!-- Ścieżka: katalog macierzysty / podkatalog / ... -->
<nav aria-label="breadcrumb">
	<ol class="breadcrumb mb-2">
		<li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door"></i> <?= htmlspecialchars($_SESSION['username']) ?></a></li>
		<?php $acc = ''; foreach ($dir === '' ? [] : explode('/', $dir) as $i => $part): $acc = join_rel($acc, $part); ?>
			<?php if ($i === $depth - 1): ?>
				<li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($part) ?></li>
			<?php else: ?>
				<li class="breadcrumb-item"><a href="<?= htmlspecialchars(dir_url($acc)) ?>"><?= htmlspecialchars($part) ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>

<!-- Pasek narzędzi -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 drive-toolbar">
	<?php if ($dir !== ''): /* 6h-ii/iii: level up tylko w podkatalogu */ ?>
		<a href="<?= htmlspecialchars(dir_url(parent_rel($dir))) ?>" class="btn btn-outline-secondary" title="Poziom wyżej (level up)">
			<i class="bi bi-arrow-90deg-up"></i> <span class="d-none d-sm-inline">W górę</span>
		</a>
	<?php endif; ?>
	<?php if ($depth < MAX_DEPTH): /* 6f-ii, 8b: nowy podkatalog tylko do dozwolonej głębokości */ ?>
		<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mkdirModal" title="Nowy podkatalog">
			<i class="bi bi-folder-plus"></i> <span class="d-none d-sm-inline">Nowy katalog</span>
		</button>
	<?php endif; ?>
	<a href="select.php<?= $dir === '' ? '' : '?' . htmlspecialchars(http_build_query(['dir' => $dir])) ?>" class="btn btn-primary" title="Prześlij plik (upload)">
		<i class="bi bi-cloud-upload"></i> <span class="d-none d-sm-inline">Prześlij pliki</span>
	</a>

	<div class="btn-group ms-auto" role="group" aria-label="Tryb wyświetlania">
		<a href="?<?= htmlspecialchars(http_build_query(['dir' => $dir, 'view' => 'list'])) ?>" class="btn btn-outline-secondary<?= $view === 'list' ? ' active' : '' ?>" title="Lista nazw plików">
			<i class="bi bi-list-ul"></i>
		</a>
		<a href="?<?= htmlspecialchars(http_build_query(['dir' => $dir, 'view' => 'preview'])) ?>" class="btn btn-outline-secondary<?= $view === 'preview' ? ' active' : '' ?>" title="Nazwy plików + miniatury i odtwarzacze">
			<i class="bi bi-grid-3x2-gap"></i>
		</a>
	</div>
</div>

<?php if (!$items): ?>
	<div class="text-center text-muted border rounded bg-white py-5">
		<i class="bi bi-folder2-open display-4 d-block mb-2"></i>
		Ten katalog jest pusty. Użyj przycisku <strong>Prześlij pliki</strong><?= $depth < MAX_DEPTH ? ' lub <strong>Nowy katalog</strong>' : '' ?>.
	</div>
<?php else: ?>
	<div class="table-responsive bg-white border rounded">
		<table class="table table-hover align-middle mb-0 drive-table">
			<thead class="table-light">
				<tr>
					<th>Nazwa</th>
					<th class="text-end d-none d-md-table-cell">Rozmiar</th>
					<th class="d-none d-md-table-cell">Data dodania</th>
					<th class="text-end">Akcje</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($items as $it):
					$rel = join_rel($dir, $it['name']);
					$name = htmlspecialchars($it['name']);
					$kind = $it['dir'] ? 'dir' : file_kind($it['name']);
					// Nazwa pliku = hiperłącze: obsługiwane formaty otwierają się w przeglądarce, pozostałe są pobierane
					$openUrl = htmlspecialchars(file_url($rel, !$it['dir'] && inline_type($it['name']) === null));
				?>
					<tr>
						<td class="text-break">
							<?php if ($it['dir']): /* 6e-ii, 6h-i: wejście do podkatalogu przez kliknięcie nazwy */ ?>
								<a href="<?= htmlspecialchars(dir_url($rel)) ?>" class="text-decoration-none fw-semibold">
									<i class="bi bi-folder-fill text-warning fs-5 me-1"></i><?= $name ?>
								</a>
								<small class="text-muted ms-1">(<?= $it['count'] ?>)</small>
							<?php else: ?>
								<?php if ($view === 'preview' && $kind === 'image'): ?>
									<a href="<?= $openUrl ?>" class="me-2 d-inline-block" data-preview="<?= $openUrl ?>" data-title="<?= $name ?>" title="Pokaż w pełnym rozmiarze">
										<img src="<?= $openUrl ?>" alt="<?= $name ?>" class="drive-thumb" loading="lazy">
									</a>
								<?php else: ?>
									<i class="bi <?= file_icon($it['name']) ?> fs-5 me-1"></i>
								<?php endif; ?>
								<a href="<?= $openUrl ?>" class="text-decoration-none" <?= inline_type($it['name']) ? 'target="_blank" rel="noopener"' : '' ?>><?= $name ?></a>
								<?php if ($view === 'preview' && $kind === 'video'): /* bez autoodtwarzania, metadane = pierwsza klatka */ ?>
									<video class="d-block mt-2 drive-player" src="<?= $openUrl ?>" controls preload="metadata"></video>
								<?php elseif ($view === 'preview' && $kind === 'audio'): ?>
									<audio class="d-block mt-2 drive-player" src="<?= $openUrl ?>" controls preload="none"></audio>
								<?php endif; ?>
								<div class="small text-muted d-md-none"><?= human_size($it['size']) ?> · <?= date('Y-m-d H:i', $it['mtime']) ?></div>
							<?php endif; ?>
						</td>
						<td class="text-end text-nowrap d-none d-md-table-cell"><?= $it['dir'] ? '-' : human_size($it['size']) ?></td>
						<td class="text-nowrap d-none d-md-table-cell"><?= date('Y-m-d H:i:s', $it['mtime']) ?></td>
						<td class="text-end text-nowrap">
							<?php if (!$it['dir']): ?>
								<a href="<?= htmlspecialchars(file_url($rel, true)) ?>" class="btn btn-sm btn-outline-success" title="Pobierz">
									<i class="bi bi-download"></i>
								</a>
							<?php endif; ?>
							<form method="post" action="usun.php" class="d-inline"
								onsubmit="return confirm(<?= htmlspecialchars(json_encode(($it['dir'] ? 'Usunąć katalog „' . $it['name'] . '” razem z całą zawartością?' : 'Usunąć plik „' . $it['name'] . '”?'), JSON_UNESCAPED_UNICODE)) ?>)">
								<?= csrf_field() ?>
								<input type="hidden" name="f" value="<?= htmlspecialchars($rel) ?>">
								<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot class="table-light small text-muted">
				<tr>
					<td colspan="4">
						<?= count(array_filter($items, fn($i) => $i['dir'])) ?> katalogów,
						<?= count(array_filter($items, fn($i) => !$i['dir'])) ?> plików, łącznie <?= human_size((int)$totalSize) ?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
<?php endif; ?>

<!-- 6f: nowy podkatalog -->
<div class="modal fade" id="mkdirModal" tabindex="-1" aria-labelledby="mkdirTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<form class="modal-content" method="post" action="nowy_katalog.php">
			<div class="modal-header">
				<h2 class="modal-title fs-5" id="mkdirTitle"><i class="bi bi-folder-plus"></i> Nowy katalog</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
			</div>
			<div class="modal-body">
				<?= csrf_field() ?>
				<input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
				<label for="mkdirName" class="form-label">Nazwa katalogu</label>
				<input type="text" class="form-control" id="mkdirName" name="name" maxlength="100" required pattern="[^.\/\\][^\/\\]*">
				<div class="form-text">Nie może zaczynać się kropką ani zawierać znaków / i \.</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
				<button type="submit" class="btn btn-primary">Utwórz</button>
			</div>
		</form>
	</div>
</div>

<!-- 6k-iv: podgląd obrazka w pełnym rozmiarze bez pobierania -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewTitle" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title fs-6 text-break" id="previewTitle"></h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
			</div>
			<div class="modal-body text-center bg-dark">
				<img id="previewImg" src="" alt="" class="img-fluid preview-full">
			</div>
		</div>
	</div>
</div>
<script>
	document.getElementById('mkdirModal').addEventListener('shown.bs.modal', function () {
		document.getElementById('mkdirName').focus();
	});
	initImagePreview();
</script>
<?php
page_end();
