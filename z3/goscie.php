<?php
// 12-16: lista wizyt gości portalu. W bazie są tylko surowe dane (IP, data, User-Agent, dane z JS);
// geolokalizacja i link do Google Maps są wyliczane z IP dopiero przy wyświetlaniu.
declare(strict_types=1);
require_once 'auth.php';
require_once 'db.php';
require_once 'geo.php';
require_once 'layout.php';

$link = db_connect();
$rows = mysqli_fetch_all(mysqli_query($link, 'SELECT * FROM goscieportalu ORDER BY id DESC'), MYSQLI_ASSOC);
mysqli_close($link);

$needUaParser = false;
page_start('Goście portalu', true);
?>
<!-- 14: Bootstrap Table - sortowanie (klik w nagłówek), filtrowanie (pole Szukaj), paginacja -->
<table id="goscie" class="table table-striped table-bordered table-sm small"
	data-pagination="true" data-page-size="10" data-page-list="[5, 10, 25, 50, all]"
	data-search="true" data-show-search-clear-button="true"
	data-show-columns="true" data-sort-name="id" data-sort-order="desc">
	<thead class="table-light">
		<tr>
			<th data-field="id" data-sortable="true">ID</th>
			<th data-field="ip" data-sortable="true">IP</th>
			<th data-field="datetime" data-sortable="true">Data i godzina</th>
			<th data-field="country" data-sortable="true">Kraj</th>
			<th data-field="region" data-sortable="true">Region</th>
			<th data-field="city" data-sortable="true">Miasto</th>
			<th data-field="loc">Współrzędne</th>
			<th data-field="map">Mapa</th>
			<th data-field="browser" data-sortable="true">Przeglądarka</th>
			<th data-field="os" data-sortable="true">System</th>
			<th data-field="screen" data-sortable="true">Ekran</th>
			<th data-field="window" data-sortable="true">Okno</th>
			<th data-field="colors" data-sortable="true">Kolory [bit]</th>
			<th data-field="cookies" data-sortable="true">Ciasteczka</th>
			<th data-field="java" data-sortable="true">Java</th>
			<th data-field="language" data-sortable="true">Język</th>
		</tr>
	</thead>
		<tbody>
			<?php foreach ($rows as $r):
				$geo = ip_details($r['ipaddress']);
				$b = parse_browser($r['browser']);
				$needUaParser = $needUaParser || ($b === null && $r['browser']);
				$ua = htmlspecialchars((string)$r['browser']);
			?>
				<tr>
					<td><?= (int)$r['id'] ?></td>
					<td><?= htmlspecialchars($r['ipaddress']) ?></td>
					<td><?= htmlspecialchars($r['datetime']) ?></td>
					<?php if ($geo): ?>
						<td><?= htmlspecialchars($geo->country ?? '-') ?></td>
						<td><?= htmlspecialchars($geo->region ?? '-') ?></td>
						<td><?= htmlspecialchars($geo->city ?? '-') ?></td>
						<td><?= htmlspecialchars($geo->loc ?? '-') ?></td>
						<td><?= maps_link($geo->loc ?? null) ?></td>
					<?php else: ?>
						<td class="text-muted">brak</td><td class="text-muted">-</td><td class="text-muted">-</td><td class="text-muted">-</td><td class="text-muted">-</td>
					<?php endif; ?>
					<?php if ($b): ?>
						<td title="<?= $ua ?>"><?= htmlspecialchars($b['browser']) ?></td>
						<td><?= htmlspecialchars($b['os']) ?></td>
					<?php else: ?>
						<td class="ua-browser" data-ua="<?= $ua ?>" title="<?= $ua ?>">-</td>
						<td class="ua-os">-</td>
					<?php endif; ?>
					<td><?= htmlspecialchars($r['screen_res'] ?? '-') ?></td>
					<td><?= htmlspecialchars($r['window_res'] ?? '-') ?></td>
					<td><?= $r['colors'] ?? '-' ?></td>
					<td><?= yes_no($r['cookies']) ?></td>
					<td><?= yes_no($r['java']) ?></td>
					<td><?= htmlspecialchars($r['language'] ?? '-') ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

<?php if ($needUaParser): /* 15: brak browscap.ini na serwerze - filtrowanie User-Agent biblioteką UAParser.js */ ?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/UAParser.js/1.0.37/ua-parser.min.js"></script>
<?php endif; ?>
<script>
	$(function () {
		if (window.UAParser) {
			$('td.ua-browser').each(function () {
				var r = new UAParser(this.dataset.ua).getResult();
				var device = r.device.type ? ' (' + r.device.type + ')' : '';
				this.textContent = [r.browser.name, r.browser.version].filter(Boolean).join(' ') || '-';
				$(this).next('.ua-os').text(([r.os.name, r.os.version].filter(Boolean).join(' ') || '-') + device);
			});
		}
		// Bootstrap Table inicjalizowana dopiero po sparsowaniu User-Agent, żeby sortowanie
		// i wyszukiwanie działały na nazwach przeglądarek, a nie na pustych komórkach
		$('#goscie').bootstrapTable();
	});
</script>
<?php
page_end();
