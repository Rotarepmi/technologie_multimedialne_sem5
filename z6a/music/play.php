<?php
// 8: odtwarzanie pojedynczego utworu - HTML5 MP3 player, w miejsce nazwy pliku wstawiona zmienna PHP
// ($src) wskazująca plik zapisany w bazie danych (song.filename), wydawany przez stream.php.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$link = db_connect();
$song = accessible_song($link, (int)($_GET['ids'] ?? 0), $idu);
$playlists = $song ? my_playlists($link, $idu) : [];
// Playlisty (moje i publiczne), na których jest ten utwór
$onPlaylists = $song ? query_all($link,
  'SELECT pn.idpl, pn.name, pn.public, u.username AS owner FROM playlistdatabase pd
   JOIN playlistname pn ON pn.idpl = pd.idpl JOIN users u ON u.id = pn.idu
   WHERE pd.ids = ? AND (pn.public = TRUE OR pn.idu = ?) ORDER BY pn.name', 'ii', (int)$song['ids'], $idu) : [];
mysqli_close($link);

if (!$song) {
  flash('warning', 'Utwór nie istnieje lub nie masz do niego dostępu.');
  redirect('index.php');
}

$ids = (int)$song['ids'];
$src = stream_url($ids);
$mine = (int)$song['idu'] === $idu;

page_start('Odtwarzanie utworu');
?>
<div class="row g-4">
	<div class="col-lg-6">
		<div class="card shadow-sm">
			<div class="card-body">
				<div class="d-flex align-items-center mb-3">
					<i class="bi bi-vinyl-fill display-5 text-success me-3"></i>
					<div class="text-break">
						<h2 class="h5 mb-0"><?= htmlspecialchars($song['title']) ?></h2>
						<div class="text-muted"><?= htmlspecialchars($song['musician']) ?></div>
					</div>
				</div>
				<audio controls autoplay preload="auto" class="w-100">
					<source src="<?= htmlspecialchars($src) ?>" type="audio/mpeg">
					Twoja przeglądarka nie obsługuje elementu audio.
				</audio>
				<dl class="row small mt-3 mb-0">
					<dt class="col-4">Gatunek</dt><dd class="col-8"><?= htmlspecialchars($song['genre']) ?></dd>
					<dt class="col-4">Dodany</dt><dd class="col-8"><?= htmlspecialchars($song['datetime']) ?> przez <?= htmlspecialchars($song['owner']) ?></dd>
					<dt class="col-4">Plik</dt><dd class="col-8 text-break">songs/<?= htmlspecialchars($song['owner'] . '/' . $song['filename']) ?></dd>
					<?php if ($onPlaylists): ?>
						<dt class="col-4">Na playlistach</dt>
						<dd class="col-8">
							<?php foreach ($onPlaylists as $pl): ?>
								<a href="../playlists/play.php?idpl=<?= (int)$pl['idpl'] ?>" class="badge text-bg-light text-decoration-none border">
									<i class="bi <?= $pl['public'] ? 'bi-globe' : 'bi-lock' ?>"></i> <?= htmlspecialchars($pl['name']) ?>
								</a>
							<?php endforeach; ?>
						</dd>
					<?php endif; ?>
				</dl>
			</div>
			<div class="card-footer bg-white d-flex flex-wrap gap-2 align-items-center">
				<?= add_to_playlist_form($ids, $playlists, '../music/play.php?ids=' . $ids) ?>
				<a href="<?= htmlspecialchars($src . '&dl=1') ?>" class="btn btn-sm btn-outline-success ms-auto" title="Pobierz MP3"><i class="bi bi-download"></i></a>
				<?php if ($mine): ?>
					<form method="post" action="usun.php" class="d-inline"
						onsubmit="return confirm(<?= htmlspecialchars(json_encode('Usunąć utwór „' . $song['title'] . '”? Zniknie też ze wszystkich playlist.', JSON_UNESCAPED_UNICODE)) ?>)">
						<?= csrf_field() ?>
						<input type="hidden" name="ids" value="<?= $ids ?>">
						<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń utwór"><i class="bi bi-trash"></i></button>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<a href="index.php" class="btn btn-link px-0 mt-2"><i class="bi bi-arrow-left"></i> Wróć do listy utworów</a>
	</div>
	<div class="col-lg-6">
		<div class="card shadow-sm">
			<div class="card-header bg-white"><i class="bi bi-card-text"></i> Tekst piosenki</div>
			<div class="card-body">
				<?php if (trim((string)$song['lyrics']) !== ''): ?>
					<pre class="lyrics mb-0"><?= htmlspecialchars($song['lyrics']) ?></pre>
				<?php else: ?>
					<span class="text-muted">Brak tekstu dla tego utworu.</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php
page_end();
