<?php
// 15: formularz tworzenia playlisty (playlistname) + lista moich playlist.
// 17a: lista publicznych playlist wszystkich pozostałych użytkowników.
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/video.php';
require_once __DIR__ . '/../layout/layout.php';

$idu = current_user_id();
$link = db_connect();
$summary = 'SELECT pn.idpl, pn.name, pn.public, pn.datetime, u.username AS owner, COUNT(pd.idpldb) AS films
  FROM playlistname pn JOIN users u ON u.id = pn.idu LEFT JOIN playlistdatabase pd ON pd.idpl = pn.idpl';
$mine = query_all($link, "{$summary} WHERE pn.idu = ? GROUP BY pn.idpl ORDER BY pn.datetime DESC", 'i', $idu);
$public = query_all($link, "{$summary} WHERE pn.public = TRUE AND pn.idu <> ? GROUP BY pn.idpl ORDER BY pn.datetime DESC", 'i', $idu);
mysqli_close($link);

page_start('Playlisty');
?>
<div class="row g-4">
	<div class="col-lg-4">
		<form method="post" action="create.php" class="card shadow-sm needs-validation" novalidate>
			<div class="card-header bg-white"><i class="bi bi-plus-circle"></i> Nowa playlista</div>
			<div class="card-body">
				<?= csrf_field() ?>
				<label for="plName" class="form-label">Nazwa</label>
				<input type="text" class="form-control mb-3" id="plName" name="name" maxlength="100" required>
				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" role="switch" id="plPublic" name="public" value="1">
					<label class="form-check-label" for="plPublic">Publiczna <span class="text-muted small">(widoczna dla wszystkich użytkowników)</span></label>
				</div>
				<button type="submit" class="btn btn-danger w-100"><i class="bi bi-plus-lg"></i> Utwórz playlistę</button>
			</div>
		</form>
	</div>

	<div class="col-lg-8">
		<h2 class="h6"><i class="bi bi-person"></i> Moje playlisty</h2>
		<?php if (!$mine): ?>
			<p class="text-muted border rounded bg-white p-3">Nie masz jeszcze żadnej playlisty.</p>
		<?php else: ?>
			<div class="table-responsive bg-white border rounded mb-4">
				<table class="table table-hover align-middle mb-0">
					<thead class="table-light">
						<tr><th>Nazwa</th><th>Widoczność</th><th class="text-end">Filmy</th><th class="d-none d-md-table-cell">Utworzona</th><th class="text-end">Akcje</th></tr>
					</thead>
					<tbody>
						<?php foreach ($mine as $pl): $idpl = (int)$pl['idpl']; ?>
							<tr>
								<td class="text-break"><a href="play.php?idpl=<?= $idpl ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($pl['name']) ?></a></td>
								<td>
									<form method="post" action="toggle.php" class="d-inline">
										<?= csrf_field() ?>
										<input type="hidden" name="idpl" value="<?= $idpl ?>">
										<button type="submit" class="btn btn-sm <?= $pl['public'] ? 'btn-outline-success' : 'btn-outline-secondary' ?>" title="Kliknij, aby zmienić">
											<i class="bi <?= $pl['public'] ? 'bi-globe' : 'bi-lock' ?>"></i> <?= $pl['public'] ? 'publiczna' : 'prywatna' ?>
										</button>
									</form>
								</td>
								<td class="text-end"><?= (int)$pl['films'] ?></td>
								<td class="d-none d-md-table-cell small text-nowrap"><?= htmlspecialchars($pl['datetime']) ?></td>
								<td class="text-end text-nowrap">
									<a href="play.php?idpl=<?= $idpl ?>" class="btn btn-sm btn-danger" title="Odtwórz"><i class="bi bi-play-fill"></i></a>
									<form method="post" action="delete.php" class="d-inline"
										onsubmit="return confirm(<?= htmlspecialchars(json_encode('Usunąć playlistę „' . $pl['name'] . '”? Filmy pozostaną w portalu.', JSON_UNESCAPED_UNICODE)) ?>)">
										<?= csrf_field() ?>
										<input type="hidden" name="idpl" value="<?= $idpl ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń playlistę"><i class="bi bi-trash"></i></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<h2 class="h6"><i class="bi bi-globe"></i> Publiczne playlisty innych użytkowników</h2>
		<?php if (!$public): ?>
			<p class="text-muted border rounded bg-white p-3">Brak publicznych playlist innych użytkowników.</p>
		<?php else: ?>
			<div class="table-responsive bg-white border rounded">
				<table class="table table-hover align-middle mb-0">
					<thead class="table-light">
						<tr><th>Nazwa</th><th>Autor</th><th class="text-end">Filmy</th><th class="d-none d-md-table-cell">Utworzona</th><th class="text-end"></th></tr>
					</thead>
					<tbody>
						<?php foreach ($public as $pl): $idpl = (int)$pl['idpl']; ?>
							<tr>
								<td class="text-break"><a href="play.php?idpl=<?= $idpl ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($pl['name']) ?></a></td>
								<td><i class="bi bi-person-circle text-secondary"></i> <?= htmlspecialchars($pl['owner']) ?></td>
								<td class="text-end"><?= (int)$pl['films'] ?></td>
								<td class="d-none d-md-table-cell small text-nowrap"><?= htmlspecialchars($pl['datetime']) ?></td>
								<td class="text-end"><a href="play.php?idpl=<?= $idpl ?>" class="btn btn-sm btn-danger" title="Odtwórz"><i class="bi bi-play-fill"></i></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
page_end();
