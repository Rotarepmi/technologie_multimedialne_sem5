<?php
// 12: odtwarzanie pojedynczego filmu - HTML5 MP4 player, w miejsce nazwy pliku wstawiona zmienna PHP
// ($src) wskazująca plik zapisany w bazie danych (film.filename), wydawany przez stream.php.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$link = db_connect();
$film = accessible_film($link, (int)($_GET['idf'] ?? 0), $idu);
$playlists = $film ? my_playlists($link, $idu) : [];
// Playlisty (moje i publiczne), na których jest ten film
$onPlaylists = $film ? query_all($link,
  'SELECT pn.idpl, pn.name, pn.public, u.username AS owner FROM playlistdatabase pd
   JOIN playlistname pn ON pn.idpl = pd.idpl JOIN users u ON u.id = pn.idu
   WHERE pd.idf = ? AND (pn.public = TRUE OR pn.idu = ?) ORDER BY pn.name', 'ii', (int)$film['idf'], $idu) : [];
mysqli_close($link);

if (!$film) {
  flash('warning', 'Film nie istnieje lub nie masz do niego dostępu.');
  redirect('index.php');
}

$idf = (int)$film['idf'];
$src = stream_url($idf);
$mine = (int)$film['idu'] === $idu;
$subtitles = trim((string)$film['subtitles']);
$timed = is_timed_subtitles($subtitles);

page_start('Odtwarzanie filmu');
?>
<div class="row g-4">
	<div class="col-xl-8">
		<div class="card shadow-sm">
			<!-- 12b-c: HTML5 MP4 player -->
			<video controls autoplay preload="auto" playsinline class="w-100 bg-black d-block film-video">
				<source src="<?= htmlspecialchars($src) ?>" type="video/mp4">
				<?php if ($timed): ?>
					<track kind="subtitles" src="<?= htmlspecialchars(subtitles_url($idf)) ?>" srclang="pl" label="Napisy" default>
				<?php endif; ?>
				Twoja przeglądarka nie obsługuje elementu video.
			</video>
			<div class="card-body">
				<h2 class="h5 mb-0 text-break"><?= htmlspecialchars($film['title']) ?></h2>
				<div class="text-muted">reż. <?= htmlspecialchars($film['director']) ?></div>
				<dl class="row small mt-3 mb-0">
					<dt class="col-4 col-md-3">Gatunek</dt><dd class="col-8 col-md-9"><?= htmlspecialchars($film['genre']) ?></dd>
					<dt class="col-4 col-md-3">Dodany</dt><dd class="col-8 col-md-9"><?= htmlspecialchars($film['datetime']) ?> przez <?= htmlspecialchars($film['owner']) ?></dd>
					<dt class="col-4 col-md-3">Plik</dt><dd class="col-8 col-md-9 text-break">films/<?= htmlspecialchars($film['owner'] . '/' . $film['filename']) ?></dd>
					<?php if ($onPlaylists): ?>
						<dt class="col-4 col-md-3">Na playlistach</dt>
						<dd class="col-8 col-md-9">
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
				<?= add_to_playlist_form($idf, $playlists, '../video/play.php?idf=' . $idf) ?>
				<a href="<?= htmlspecialchars($src . '&dl=1') ?>" class="btn btn-sm btn-outline-danger ms-auto" title="Pobierz MP4"><i class="bi bi-download"></i></a>
				<?php if ($mine): ?>
					<form method="post" action="usun.php" class="d-inline"
						onsubmit="return confirm(<?= htmlspecialchars(json_encode('Usunąć film „' . $film['title'] . '”? Zniknie też ze wszystkich playlist.', JSON_UNESCAPED_UNICODE)) ?>)">
						<?= csrf_field() ?>
						<input type="hidden" name="idf" value="<?= $idf ?>">
						<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń film"><i class="bi bi-trash"></i></button>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<a href="index.php" class="btn btn-link px-0 mt-2"><i class="bi bi-arrow-left"></i> Wróć do listy filmów</a>
	</div>
	<div class="col-xl-4">
		<div class="card shadow-sm">
			<div class="card-header bg-white"><i class="bi bi-badge-cc"></i> Napisy</div>
			<div class="card-body">
				<?php if ($subtitles !== ''): ?>
					<?php if ($timed): ?>
						<p class="small text-muted">Napisy z kodami czasowymi są wyświetlane w odtwarzaczu (przycisk CC).</p>
					<?php endif; ?>
					<pre class="subtitles mb-0"><?= htmlspecialchars($subtitles) ?></pre>
				<?php else: ?>
					<span class="text-muted">Brak napisów dla tego filmu.</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php
page_end();
