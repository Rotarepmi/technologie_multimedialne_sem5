<?php
// Lista filmów z podglądem (HTML5 MP4 player), wyszukiwaniem (tytuł/reżyser) i filtrem gatunku.
// Zakres: "moje" = filmy przesłane przez zalogowanego użytkownika,
//         "dostępne" = także cudze filmy z publicznych playlist i z moich playlist (17a-b).
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$scope = ($_GET['scope'] ?? '') === 'all' ? 'all' : 'mine';
$q = trim((string)($_GET['q'] ?? ''));
$idft = (int)($_GET['idft'] ?? 0);

$where = $scope === 'mine'
  ? 'f.idu = ?'
  : '(f.idu = ? OR EXISTS (SELECT 1 FROM playlistdatabase pd JOIN playlistname pn ON pn.idpl = pd.idpl
       WHERE pd.idf = f.idf AND (pn.public = TRUE OR pn.idu = ?)))';
$types = $scope === 'mine' ? 'i' : 'ii';
$params = $scope === 'mine' ? [$idu] : [$idu, $idu];
if ($q !== '') {
  $where .= ' AND (f.title LIKE ? OR f.director LIKE ?)';
  $like = '%' . addcslashes($q, '%_\\') . '%';
  $types .= 'ss';
  array_push($params, $like, $like);
}
if ($idft > 0) {
  $where .= ' AND f.idft = ?';
  $types .= 'i';
  $params[] = $idft;
}

$link = db_connect();
$films = query_all($link, 'SELECT ' . FILM_COLUMNS . ' FROM film f ' . FILM_JOINS . " WHERE {$where} ORDER BY f.datetime DESC, f.idf DESC", $types, ...$params);
$genres = genres($link);
$playlists = my_playlists($link, $idu);
mysqli_close($link);

$qs = (string)($_SERVER['QUERY_STRING'] ?? '');
$back = '../video/index.php' . ($qs !== '' ? '?' . $qs : ''); // powrót po dodaniu do playlisty

page_start($scope === 'mine' ? 'Moje filmy' : 'Wszystkie dostępne filmy');
?>
<ul class="nav nav-tabs mb-3">
	<li class="nav-item"><a class="nav-link<?= $scope === 'mine' ? ' active' : '' ?>" href="index.php"><i class="bi bi-person"></i> Moje filmy</a></li>
	<li class="nav-item"><a class="nav-link<?= $scope === 'all' ? ' active' : '' ?>" href="index.php?scope=all"><i class="bi bi-globe"></i> Dostępne (moje + z publicznych playlist)</a></li>
</ul>

<form method="get" class="row g-2 align-items-center mb-3">
	<input type="hidden" name="scope" value="<?= $scope ?>">
	<div class="col-sm-6 col-lg-4">
		<input type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Szukaj: tytuł lub reżyser">
	</div>
	<div class="col-sm-4 col-lg-3">
		<select name="idft" class="form-select" aria-label="Gatunek">
			<option value="0">Wszystkie gatunki</option>
			<?php foreach ($genres as $g): ?>
				<option value="<?= (int)$g['idft'] ?>"<?= (int)$g['idft'] === $idft ? ' selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-auto">
		<button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filtruj</button>
		<?php if ($q !== '' || $idft > 0): ?>
			<a href="index.php<?= $scope === 'all' ? '?scope=all' : '' ?>" class="btn btn-link">Wyczyść</a>
		<?php endif; ?>
	</div>
	<div class="col-auto ms-auto">
		<a href="select.php" class="btn btn-danger"><i class="bi bi-cloud-upload"></i> Dodaj film</a>
	</div>
</form>

<?php if (!$playlists): ?>
	<div class="alert alert-info py-2 small">
		<i class="bi bi-info-circle"></i> Aby dodawać filmy do playlist, najpierw <a href="../playlists/index.php">utwórz playlistę</a>.
	</div>
<?php endif; ?>

<?php if (!$films): ?>
	<div class="text-center text-muted border rounded bg-white py-5">
		<i class="bi bi-film display-4 d-block mb-2"></i>
		<?= $q !== '' || $idft > 0 ? 'Brak filmów spełniających kryteria.' : 'Brak filmów. Użyj przycisku <strong>Dodaj film</strong>.' ?>
	</div>
<?php else: ?>
	<div class="table-responsive bg-white border rounded">
		<table class="table table-hover align-middle mb-0 film-table">
			<thead class="table-light">
				<tr>
					<th>Podgląd</th>
					<th>Tytuł / reżyser</th>
					<th class="d-none d-md-table-cell">Gatunek</th>
					<th class="d-none d-lg-table-cell">Dodany</th>
					<th class="text-end">Akcje</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($films as $f): $idf = (int)$f['idf']; $mine = (int)$f['idu'] === $idu; ?>
					<tr>
						<td>
							<!-- 12b-c: HTML5 MP4 player, źródło = nazwa pliku z bazy danych (przez stream.php);
							     preload="metadata" + #t=1 pokazuje klatkę z 1. sekundy jako miniaturę -->
							<video class="film-thumb" src="<?= htmlspecialchars(stream_url($idf)) ?>#t=1" controls preload="metadata" playsinline></video>
						</td>
						<td class="text-break">
							<a href="play.php?idf=<?= $idf ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($f['title']) ?></a>
							<?php if (trim((string)$f['subtitles']) !== ''): ?><i class="bi bi-badge-cc text-muted ms-1" title="Ma napisy"></i><?php endif; ?>
							<div class="small text-muted">reż. <?= htmlspecialchars($f['director']) ?><?= $mine ? '' : ' · dodał(a): ' . htmlspecialchars($f['owner']) ?></div>
						</td>
						<td class="d-none d-md-table-cell"><span class="badge text-bg-secondary"><?= htmlspecialchars($f['genre']) ?></span></td>
						<td class="d-none d-lg-table-cell text-nowrap small"><?= htmlspecialchars($f['datetime']) ?></td>
						<td class="text-end text-nowrap">
							<a href="play.php?idf=<?= $idf ?>" class="btn btn-sm btn-danger" title="Odtwórz"><i class="bi bi-play-fill"></i></a>
							<?= add_to_playlist_form($idf, $playlists, $back) ?>
							<?php if ($mine): ?>
								<form method="post" action="usun.php" class="d-inline"
									onsubmit="return confirm(<?= htmlspecialchars(json_encode('Usunąć film „' . $f['title'] . '”? Zniknie też ze wszystkich playlist.', JSON_UNESCAPED_UNICODE)) ?>)">
									<?= csrf_field() ?>
									<input type="hidden" name="idf" value="<?= $idf ?>">
									<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń film"><i class="bi bi-trash"></i></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot class="table-light small text-muted">
				<tr><td colspan="5">Filmów: <?= count($films) ?></td></tr>
			</tfoot>
		</table>
	</div>
<?php endif; ?>
<script>
	// Tylko jeden film gra naraz
	document.addEventListener('play', function (e) {
		document.querySelectorAll('video').forEach(function (v) { if (v !== e.target) v.pause(); });
	}, true);
</script>
<?php
page_end();
