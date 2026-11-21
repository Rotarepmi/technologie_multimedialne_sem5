<?php
// 16, 17c: odtwarzanie playlisty. Jeden element <video> (HTML5 MP4 player), którego źródło zmienia
// skrypt: po zdarzeniu "ended" automatycznie startuje następny film - bez klikania.
// Adresy plików to zmienne PHP pobrane dla danej playlisty z bazy danych (12c).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$idpl = (int)($_GET['idpl'] ?? 0);
$link = db_connect();
$pl = accessible_playlist($link, $idpl, $idu);
$films = $pl ? playlist_films($link, $idpl) : [];
$playlists = $pl ? my_playlists($link, $idu) : [];
mysqli_close($link);

if (!$pl) {
  flash('warning', 'Playlista nie istnieje lub jest prywatna.');
  redirect('index.php');
}

$mine = (int)$pl['idu'] === $idu;
// Do formularza "dodaj do playlisty" nie podpowiadamy bieżącej playlisty
$targets = array_values(array_filter($playlists, fn($p) => (int)$p['idpl'] !== $idpl));
$back = '../playlists/play.php?idpl=' . $idpl;

$items = array_map(fn($f) => [
  'idf' => (int)$f['idf'],
  'title' => $f['title'],
  'director' => $f['director'],
  'src' => stream_url((int)$f['idf']),
  'subtitles' => (string)$f['subtitles'],
  'track' => is_timed_subtitles($f['subtitles']) ? subtitles_url((int)$f['idf']) : null,
], $films);

page_start('Playlista: ' . $pl['name']);
?>
<p class="text-muted mb-3">
	<i class="bi <?= $pl['public'] ? 'bi-globe' : 'bi-lock' ?>"></i> <?= $pl['public'] ? 'Publiczna' : 'Prywatna' ?>
	· autor: <strong><?= htmlspecialchars($pl['owner']) ?></strong>
	· utworzona <?= htmlspecialchars($pl['datetime']) ?>
	· filmów: <?= count($films) ?>
	<a href="index.php" class="ms-2"><i class="bi bi-arrow-left"></i> Wszystkie playlisty</a>
</p>

<?php if (!$films): ?>
	<div class="text-center text-muted border rounded bg-white py-5">
		<i class="bi bi-collection-play display-4 d-block mb-2"></i>
		Playlista jest pusta.
		<?php if ($mine): ?>
			Dodaj filmy w zakładce <a href="../video/index.php">Moje filmy</a> lub <a href="../video/index.php?scope=all">Dostępne filmy</a>.
		<?php endif; ?>
	</div>
<?php else: ?>
<div class="row g-4">
	<div class="col-lg-8">
		<div class="card shadow-sm player-card">
			<!-- 16b: HTML5 MP4 player playlisty -->
			<video id="plVideo" class="w-100 bg-black d-block film-video" controls preload="auto" playsinline>
				Twoja przeglądarka nie obsługuje elementu video.
			</video>
			<div class="card-body">
				<div class="small text-muted">Teraz odtwarzany <span id="plPosition"></span></div>
				<h2 class="h5 mb-0 text-break" id="plTitle"></h2>
				<div class="text-muted" id="plDirector"></div>
				<div class="d-flex flex-wrap gap-2 mt-3">
					<button type="button" class="btn btn-outline-secondary" id="plPrev" title="Poprzedni film"><i class="bi bi-skip-start-fill"></i></button>
					<button type="button" class="btn btn-danger flex-grow-1" id="plToggle"><i class="bi bi-play-fill"></i> Odtwórz playlistę</button>
					<button type="button" class="btn btn-outline-secondary" id="plNext" title="Następny film"><i class="bi bi-skip-end-fill"></i></button>
					<input type="checkbox" class="btn-check" id="plRepeat" autocomplete="off">
					<label class="btn btn-outline-secondary" for="plRepeat" title="Zapętl playlistę"><i class="bi bi-repeat"></i></label>
					<input type="checkbox" class="btn-check" id="plShuffle" autocomplete="off">
					<label class="btn btn-outline-secondary" for="plShuffle" title="Kolejność losowa"><i class="bi bi-shuffle"></i></label>
				</div>
				<div class="form-text">Po zakończeniu filmu automatycznie odtwarzany jest następny (także w trybie pełnoekranowym).</div>
			</div>
			<div class="card-footer bg-white">
				<a class="small text-decoration-none" data-bs-toggle="collapse" href="#plSubtitlesBox" role="button" aria-expanded="false">
					<i class="bi bi-badge-cc"></i> Napisy
				</a>
				<div class="collapse mt-2" id="plSubtitlesBox">
					<pre class="subtitles small mb-0" id="plSubtitles"></pre>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4">
		<div class="list-group shadow-sm track-list" id="plTracks">
			<?php foreach ($films as $i => $f): ?>
				<div class="list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-2 track" data-index="<?= $i ?>">
					<span class="track-no text-muted small"><?= $i + 1 ?></span>
					<i class="bi bi-play-circle text-danger fs-5 track-icon"></i>
					<div class="flex-grow-1 text-break">
						<div><?= htmlspecialchars($f['title']) ?></div>
						<div class="small text-muted">
							reż. <?= htmlspecialchars($f['director']) ?> · <?= htmlspecialchars($f['genre']) ?>
							<?= $f['owner'] !== $_SESSION['username'] ? ' · dodał(a): ' . htmlspecialchars($f['owner']) : '' ?>
						</div>
					</div>
					<div class="text-nowrap">
						<?= add_to_playlist_form((int)$f['idf'], $targets, $back) /* 17b: także filmy z cudzych publicznych playlist */ ?>
						<a href="../video/play.php?idf=<?= (int)$f['idf'] ?>" class="btn btn-sm btn-outline-secondary" title="Szczegóły filmu"><i class="bi bi-info-lg"></i></a>
						<?php if ($mine): ?>
							<form method="post" action="remove.php" class="d-inline">
								<?= csrf_field() ?>
								<input type="hidden" name="idpldb" value="<?= (int)$f['idpldb'] ?>">
								<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń z playlisty"><i class="bi bi-x-lg"></i></button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if (!$mine && !$playlists): ?>
			<div class="form-text mt-2">Aby dodać te filmy do siebie, <a href="index.php">utwórz własną playlistę</a>.</div>
		<?php endif; ?>
	</div>
</div>
<script>
	initPlaylistPlayer(<?= json_encode($items, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
<?php endif; ?>
<?php
page_end();
