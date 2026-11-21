<?php
// 12, 13c: odtwarzanie playlisty. Jeden element <audio> (HTML5 MP3 player), którego źródło zmienia
// skrypt: po zdarzeniu "ended" automatycznie startuje następny utwór - bez klikania.
// Adresy plików to zmienne PHP pobrane dla danej playlisty z bazy danych (12c).
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$idpl = (int)($_GET['idpl'] ?? 0);
$link = db_connect();
$pl = accessible_playlist($link, $idpl, $idu);
$songs = $pl ? playlist_songs($link, $idpl) : [];
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

$tracks = array_map(fn($s) => [
  'ids' => (int)$s['ids'],
  'title' => $s['title'],
  'musician' => $s['musician'],
  'src' => stream_url((int)$s['ids']),
  'lyrics' => (string)$s['lyrics'],
], $songs);

page_start('Playlista: ' . $pl['name']);
?>
<p class="text-muted mb-3">
	<i class="bi <?= $pl['public'] ? 'bi-globe' : 'bi-lock' ?>"></i> <?= $pl['public'] ? 'Publiczna' : 'Prywatna' ?>
	· autor: <strong><?= htmlspecialchars($pl['owner']) ?></strong>
	· utworzona <?= htmlspecialchars($pl['datetime']) ?>
	· utworów: <?= count($songs) ?>
	<a href="index.php" class="ms-2"><i class="bi bi-arrow-left"></i> Wszystkie playlisty</a>
</p>

<?php if (!$songs): ?>
	<div class="text-center text-muted border rounded bg-white py-5">
		<i class="bi bi-music-note-list display-4 d-block mb-2"></i>
		Playlista jest pusta.
		<?php if ($mine): ?>
			Dodaj utwory w zakładce <a href="../music/index.php">Moje utwory</a> lub <a href="../music/index.php?scope=all">Dostępne utwory</a>.
		<?php endif; ?>
	</div>
<?php else: ?>
<div class="row g-4">
	<div class="col-lg-5">
		<div class="card shadow-sm player-card">
			<div class="card-body">
				<div class="d-flex align-items-center mb-3">
					<i class="bi bi-vinyl-fill display-5 text-success me-3" id="plDisc"></i>
					<div class="text-break flex-grow-1">
						<div class="small text-muted">Teraz odtwarzane <span id="plPosition"></span></div>
						<h2 class="h5 mb-0" id="plTitle"></h2>
						<div class="text-muted" id="plMusician"></div>
					</div>
				</div>
				<audio id="plAudio" controls preload="auto">
					Twoja przeglądarka nie obsługuje elementu audio.
				</audio>
				<div class="d-flex flex-wrap gap-2 mt-2">
					<button type="button" class="btn btn-outline-secondary" id="plPrev" title="Poprzedni utwór"><i class="bi bi-skip-start-fill"></i></button>
					<button type="button" class="btn btn-success flex-grow-1" id="plToggle"><i class="bi bi-play-fill"></i> Odtwórz playlistę</button>
					<button type="button" class="btn btn-outline-secondary" id="plNext" title="Następny utwór"><i class="bi bi-skip-end-fill"></i></button>
					<input type="checkbox" class="btn-check" id="plRepeat" autocomplete="off">
					<label class="btn btn-outline-secondary" for="plRepeat" title="Zapętl playlistę"><i class="bi bi-repeat"></i></label>
					<input type="checkbox" class="btn-check" id="plShuffle" autocomplete="off">
					<label class="btn btn-outline-secondary" for="plShuffle" title="Kolejność losowa"><i class="bi bi-shuffle"></i></label>
				</div>
				<div class="form-text">Po zakończeniu utworu automatycznie odtwarzany jest następny.</div>
			</div>
			<div class="card-footer bg-white">
				<a class="small text-decoration-none" data-bs-toggle="collapse" href="#plLyricsBox" role="button" aria-expanded="false">
					<i class="bi bi-card-text"></i> Tekst piosenki
				</a>
				<div class="collapse mt-2" id="plLyricsBox">
					<pre class="lyrics small mb-0" id="plLyrics"></pre>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-7">
		<div class="list-group shadow-sm track-list" id="plTracks">
			<?php foreach ($songs as $i => $s): ?>
				<div class="list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-2 track" data-index="<?= $i ?>">
					<span class="track-no text-muted small"><?= $i + 1 ?></span>
					<i class="bi bi-play-circle text-success fs-5 track-icon"></i>
					<div class="flex-grow-1 text-break">
						<div><?= htmlspecialchars($s['title']) ?></div>
						<div class="small text-muted">
							<?= htmlspecialchars($s['musician']) ?> · <?= htmlspecialchars($s['genre']) ?>
							<?= $s['owner'] !== $_SESSION['username'] ? ' · dodał(a): ' . htmlspecialchars($s['owner']) : '' ?>
						</div>
					</div>
					<div class="text-nowrap">
						<?= add_to_playlist_form((int)$s['ids'], $targets, $back) /* 13b: także utwory z cudzych publicznych playlist */ ?>
						<a href="../music/play.php?ids=<?= (int)$s['ids'] ?>" class="btn btn-sm btn-outline-secondary" title="Szczegóły utworu"><i class="bi bi-info-lg"></i></a>
						<?php if ($mine): ?>
							<form method="post" action="remove.php" class="d-inline">
								<?= csrf_field() ?>
								<input type="hidden" name="idpldb" value="<?= (int)$s['idpldb'] ?>">
								<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń z playlisty"><i class="bi bi-x-lg"></i></button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if (!$mine && !$playlists): ?>
			<div class="form-text mt-2">Aby dodać te utwory do siebie, <a href="index.php">utwórz własną playlistę</a>.</div>
		<?php endif; ?>
	</div>
</div>
<script>
	initPlaylistPlayer(<?= json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
<?php endif; ?>
<?php
page_end();
