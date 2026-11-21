<?php
// 6b: udane i błędne próby logowania (oraz wizyty gości portalu z z3) z tabeli goscieportalu - widok administratora
declare(strict_types=1);
require_once 'auth.php';
require_admin();
require_once 'db.php';
require_once 'layout.php';

$link = db_connect();
$rows = mysqli_fetch_all(mysqli_query($link,
  'SELECT id, datetime, event, username, ipaddress, browser, screen_res, language FROM goscieportalu ORDER BY id DESC'), MYSQLI_ASSOC);
mysqli_close($link);

$events = [
  'login_ok' => ['Udane logowanie', 'success'],
  'login_fail' => ['Błędne logowanie', 'danger'],
  'visit' => ['Wizyta', 'secondary'],
];

page_start('Logowania i wizyty gości portalu', true);
?>
<table id="logowania" class="table table-striped table-bordered table-sm small bg-white"
	data-toggle="table" data-pagination="true" data-page-size="25" data-page-list="[10, 25, 50, 100, all]"
	data-search="true" data-show-search-clear-button="true" data-sort-name="id" data-sort-order="desc">
	<thead class="table-light">
		<tr>
			<th data-field="id" data-sortable="true">ID</th>
			<th data-field="datetime" data-sortable="true">Data i godzina</th>
			<th data-field="event" data-sortable="true">Zdarzenie</th>
			<th data-field="username" data-sortable="true">Login</th>
			<th data-field="ip" data-sortable="true">IP</th>
			<th data-field="browser">Przeglądarka (User-Agent)</th>
			<th data-field="screen" data-sortable="true">Ekran</th>
			<th data-field="language" data-sortable="true">Język</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($rows as $r): [$label, $color] = $events[$r['event']] ?? [$r['event'], 'secondary']; ?>
			<tr>
				<td><?= (int)$r['id'] ?></td>
				<td class="text-nowrap"><?= htmlspecialchars($r['datetime']) ?></td>
				<td><span class="badge text-bg-<?= $color ?>"><?= $label ?></span></td>
				<td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
				<td><?= htmlspecialchars($r['ipaddress']) ?></td>
				<td class="text-truncate" style="max-width: 320px" title="<?= htmlspecialchars((string)$r['browser']) ?>"><?= htmlspecialchars((string)$r['browser']) ?></td>
				<td><?= htmlspecialchars($r['screen_res'] ?? '-') ?></td>
				<td><?= htmlspecialchars($r['language'] ?? '-') ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
page_end();
