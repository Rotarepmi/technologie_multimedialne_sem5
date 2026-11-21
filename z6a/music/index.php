<?php
// 8, 11b: lista utworów z odtwarzaczem HTML5, wyszukiwaniem (tytuł/wykonawca) i filtrem gatunku.
// Zakres: "moje" = utwory przesłane przez zalogowanego użytkownika,
//         "dostępne" = także cudze utwory z publicznych playlist i z moich playlist (13a-b).
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$scope = ($_GET['scope'] ?? '') === 'all' ? 'all' : 'mine';
$q = trim((string)($_GET['q'] ?? ''));
$idmt = (int)($_GET['idmt'] ?? 0);

$where = $scope === 'mine'
  ? 's.idu = ?'
  : '(s.idu = ? OR EXISTS (SELECT 1 FROM playlistdatabase pd JOIN playlistname pn ON pn.idpl = pd.idpl
       WHERE pd.ids = s.ids AND (pn.public = TRUE OR pn.idu = ?)))';
$types = $scope === 'mine' ? 'i' : 'ii';
$params = $scope === 'mine' ? [$idu] : [$idu, $idu];
if ($q !== '') {
  $where .= ' AND (s.title LIKE ? OR s.musician LIKE ?)';
  $like = '%' . addcslashes($q, '%_\\') . '%';
  $types .= 'ss';
  array_push($params, $like, $like);
}
if ($idmt > 0) {
  $where .= ' AND s.idmt = ?';
  $types .= 'i';
  $params[] = $idmt;
}

$link = db_connect();
$songs = query_all($link, 'SELECT ' . SONG_COLUMNS . ' FROM song s ' . SONG_JOINS . " WHERE {$where} ORDER BY s.datetime DESC, s.ids DESC", $types, ...$params);
$genres = genres($link);
$playlists = my_playlists($link, $idu);
mysqli_close($link);

$qs = (string)($_SERVER['QUERY_STRING'] ?? '');
$back = '../music/index.php' . ($qs !== '' ? '?' . $qs : ''); // powrót po dodaniu do playlisty

page_start($scope === 'mine' ? 'Moje utwory' : 'Wszystkie dostępne utwory');
?>
<ul class="nav nav-tabs mb-3">
	<li class="nav-item"><a class="nav-link<?= $scope === 'mine' ? ' active' : '' ?>" href="index.php"><i class="bi bi-person"></i> Moje utwory</a></li>
	<li class="nav-item"><a class="nav-link<?= $scope === 'all' ? ' active' : '' ?>" href="index.php?scope=all"><i class="bi bi-globe"></i> Dostępne (moje + z publicznych playlist)</a></li>
</ul>

<form method="get" class="row g-2 align-items-center mb-3">
	<input type="hidden" name="scope" value="<?= $scope ?>">
	<div class="col-sm-6 col-lg-4">
		<input type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Szukaj: tytuł lub wykonawca">
	</div>
	<div class="col-sm-4 col-lg-3">
		<select name="idmt" class="form-select" aria-label="Gatunek">
			<option value="0">Wszystkie gatunki</option>
			<?php foreach ($genres as $g): ?>
				<option value="<?= (int)$g['idmt'] ?>"<?= (int)$g['idmt'] === $idmt ? ' selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-auto">
		<button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filtruj</button>
		<?php if ($q !== '' || $idmt > 0): ?>
			<a href="index.php<?= $scope === 'all' ? '?scope=all' : '' ?>" class="btn btn-link">Wyczyść</a>
		<?php endif; ?>
	</div>
	<div class="col-auto ms-auto">
		<a href="select.php" class="btn btn-success"><i class="bi bi-cloud-upload"></i> Dodaj utwór</a>
	</div>
</form>

<?php if (!$playlists): ?>
	<div class="alert alert-info py-2 small">
		<i class="bi bi-info-circle"></i> Aby dodawać utwory do playlist, najpierw <a href="../playlists/index.php">utwórz playlistę</a>.
	</div>
<?php endif; ?>

<?php if (!$songs): ?>
	<div class="text-center text-muted border rounded bg-white py-5">
		<i class="bi bi-music-note-list display-4 d-block mb-2"></i>
		<?= $q !== '' || $idmt > 0 ? 'Brak utworów spełniających kryteria.' : 'Brak utworów. Użyj przycisku <strong>Dodaj utwór</strong>.' ?>
	</div>
<?php else: ?>
	<div class="table-responsive bg-white border rounded">
		<table class="table table-hover align-middle mb-0 song-table">
			<thead class="table-light">
				<tr>
					<th>Tytuł / wykonawca</th>
					<th>Odtwarzacz</th>
					<th class="d-none d-md-table-cell">Gatunek</th>
					<th class="d-none d-lg-table-cell">Dodany</th>
					<th class="text-end">Akcje</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($songs as $s): $ids = (int)$s['ids']; $mine = (int)$s['idu'] === $idu; ?>
					<tr>
						<td class="text-break">
							<a href="play.php?ids=<?= $ids ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($s['title']) ?></a>
							<?php if (trim((string)$s['lyrics']) !== ''): ?><i class="bi bi-card-text text-muted ms-1" title="Ma tekst piosenki"></i><?php endif; ?>
							<div class="small text-muted"><?= htmlspecialchars($s['musician']) ?><?= $mine ? '' : ' · dodał(a): ' . htmlspecialchars($s['owner']) ?></div>
						</td>
						<td>
							<!-- 8b-c: HTML5 MP3 player, źródło = nazwa pliku z bazy danych (przez stream.php) -->
							<audio class="song-player" src="<?= htmlspecialchars(stream_url($ids)) ?>" controls preload="none"></audio>
						</td>
						<td class="d-none d-md-table-cell"><span class="badge text-bg-secondary"><?= htmlspecialchars($s['genre']) ?></span></td>
						<td class="d-none d-lg-table-cell text-nowrap small"><?= htmlspecialchars($s['datetime']) ?></td>
						<td class="text-end text-nowrap">
							<?= add_to_playlist_form($ids, $playlists, $back) ?>
							<?php if ($mine): ?>
								<form method="post" action="usun.php" class="d-inline"
									onsubmit="return confirm(<?= htmlspecialchars(json_encode('Usunąć utwór „' . $s['title'] . '”? Zniknie też ze wszystkich playlist.', JSON_UNESCAPED_UNICODE)) ?>)">
									<?= csrf_field() ?>
									<input type="hidden" name="ids" value="<?= $ids ?>">
									<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń utwór"><i class="bi bi-trash"></i></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot class="table-light small text-muted">
				<tr><td colspan="5">Utworów: <?= count($songs) ?></td></tr>
			</tfoot>
		</table>
	</div>
<?php endif; ?>
<script>
	// Tylko jeden utwór gra naraz
	document.addEventListener('play', function (e) {
		document.querySelectorAll('audio').forEach(function (a) { if (a !== e.target) a.pause(); });
	}, true);
</script>
<?php
page_end();
