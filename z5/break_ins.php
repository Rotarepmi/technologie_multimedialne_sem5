<?php
// 6b-iv: zawartość tabeli break_ins (3 kolejne błędne logowania -> blokada na 1 min) - widok administratora.
// Zestawienie wg IP ułatwia tworzenie czarnych list adresów.
declare(strict_types=1);
require_once 'auth.php';
require_admin();
require_once 'db.php';
require_once 'layout.php';

$link = db_connect();
$rows = mysqli_fetch_all(mysqli_query($link, 'SELECT id, datetime, username, ipaddress FROM break_ins ORDER BY id DESC'), MYSQLI_ASSOC);
$byIp = mysqli_fetch_all(mysqli_query($link,
  'SELECT ipaddress, COUNT(*) AS cnt, COUNT(DISTINCT username) AS accounts, MAX(datetime) AS last
   FROM break_ins GROUP BY ipaddress ORDER BY cnt DESC, last DESC'), MYSQLI_ASSOC);
mysqli_close($link);

page_start('Próby włamań (break_ins)', true);
?>
<div class="row g-4">
	<div class="col-lg-7">
		<h2 class="h6">Zdarzenia</h2>
		<table class="table table-striped table-bordered table-sm small bg-white"
			data-toggle="table" data-pagination="true" data-page-size="25" data-search="true" data-sort-name="id" data-sort-order="desc">
			<thead class="table-light">
				<tr>
					<th data-field="id" data-sortable="true">ID</th>
					<th data-field="datetime" data-sortable="true">Data i godzina</th>
					<th data-field="username" data-sortable="true">Atakowane konto</th>
					<th data-field="ip" data-sortable="true">IP</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
					<tr>
						<td><?= (int)$r['id'] ?></td>
						<td><?= htmlspecialchars($r['datetime']) ?></td>
						<td><?= htmlspecialchars($r['username']) ?></td>
						<td><?= htmlspecialchars($r['ipaddress']) ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<div class="col-lg-5">
		<h2 class="h6">Adresy IP (kandydaci do czarnej listy)</h2>
		<table class="table table-striped table-bordered table-sm small bg-white"
			data-toggle="table" data-sort-name="cnt" data-sort-order="desc">
			<thead class="table-light">
				<tr>
					<th data-field="ip" data-sortable="true">IP</th>
					<th data-field="cnt" data-sortable="true">Blokad</th>
					<th data-field="accounts" data-sortable="true">Kont</th>
					<th data-field="last" data-sortable="true">Ostatnio</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($byIp as $r): ?>
					<tr>
						<td><?= htmlspecialchars($r['ipaddress']) ?></td>
						<td><?= (int)$r['cnt'] ?></td>
						<td><?= (int)$r['accounts'] ?></td>
						<td><?= htmlspecialchars($r['last']) ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
page_end();
