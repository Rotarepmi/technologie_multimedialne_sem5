<?php
// 17b-c: tylko unikalne IP gości portalu, z liczbą wejść z danego IP, posortowane malejąco wg liczby wejść
declare(strict_types=1);
require_once 'auth.php';
require_once 'db.php';
require_once 'geo.php';
require_once 'layout.php';

$link = db_connect();
$rows = mysqli_fetch_all(mysqli_query($link,
  'SELECT ipaddress, COUNT(*) AS visits, MIN(datetime) AS first_visit, MAX(datetime) AS last_visit
   FROM goscieportalu GROUP BY ipaddress ORDER BY visits DESC, last_visit DESC'), MYSQLI_ASSOC);
mysqli_close($link);

page_start('Wizyty wg IP', true);
?>
<!-- 17c: domyślnie malejąco wg liczby wejść -->
<table id="goscie-ip" class="table table-striped table-bordered table-sm small"
	data-toggle="table" data-pagination="true" data-page-size="10" data-page-list="[5, 10, 25, 50, all]"
	data-search="true" data-show-search-clear-button="true"
	data-sort-name="visits" data-sort-order="desc">
	<thead class="table-light">
		<tr>
			<th data-field="ip" data-sortable="true">IP</th>
			<th data-field="visits" data-sortable="true">Liczba wejść</th>
			<th data-field="first" data-sortable="true">Pierwsza wizyta</th>
			<th data-field="last" data-sortable="true">Ostatnia wizyta</th>
			<th data-field="country" data-sortable="true">Kraj</th>
			<th data-field="city" data-sortable="true">Miasto</th>
			<th data-field="map">Mapa</th>
		</tr>
	</thead>
		<tbody>
			<?php foreach ($rows as $r): $geo = ip_details($r['ipaddress']); ?>
				<tr>
					<td><?= htmlspecialchars($r['ipaddress']) ?></td>
					<td><?= (int)$r['visits'] ?></td>
					<td><?= htmlspecialchars($r['first_visit']) ?></td>
					<td><?= htmlspecialchars($r['last_visit']) ?></td>
					<td><?= htmlspecialchars($geo->country ?? '-') ?></td>
					<td><?= htmlspecialchars($geo->city ?? '-') ?></td>
					<td><?= maps_link($geo->loc ?? null) ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php
page_end();
