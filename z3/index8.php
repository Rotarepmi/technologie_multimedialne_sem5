<?php
// 11: przybliżona geolokalizacja gościa portalu na podstawie IP (ipinfo.io, JSON)
declare(strict_types=1);
require_once 'auth.php';
require_once 'geo.php';
require_once 'layout.php';

// ?ip= pozwala sprawdzić dowolny adres (przydatne lokalnie, gdzie REMOTE_ADDR jest prywatny)
$ipaddress = filter_var($_GET['ip'] ?? '', FILTER_VALIDATE_IP) ?: $_SERVER['REMOTE_ADDR'];
$details = ip_details($ipaddress);

page_start('Geolokalizacja IP - ipinfo.io');
?>
<form class="row g-2 mb-3" method="get">
	<div class="col-auto"><input class="form-control form-control-sm" name="ip" value="<?= htmlspecialchars($ipaddress) ?>" placeholder="adres IP"></div>
	<div class="col-auto"><button class="btn btn-sm btn-primary">Sprawdź</button></div>
</form>
<?php if (!$details): ?>
	<div class="alert alert-warning">Brak danych dla <?= htmlspecialchars($ipaddress) ?> - adres prywatny/zarezerwowany albo ipinfo.io nie odpowiada.
		Wpisz publiczny IP (np. 8.8.8.8), aby przetestować skrypt.</div>
<?php else: ?>
	<table class="table table-sm table-bordered w-auto bg-white">
		<tr><th>Region</th><td><?= htmlspecialchars($details->region ?? '-') ?></td></tr>
		<tr><th>Kraj</th><td><?= htmlspecialchars($details->country ?? '-') ?></td></tr>
		<tr><th>Miasto</th><td><?= htmlspecialchars($details->city ?? '-') ?></td></tr>
		<tr><th>Współrzędne</th><td><?= htmlspecialchars($details->loc ?? '-') ?></td></tr>
		<tr><th>IP</th><td><?= htmlspecialchars($details->ip ?? $ipaddress) ?></td></tr>
		<tr><th>Google Maps</th><td><?= maps_link($details->loc ?? null) ?></td></tr>
	</table>
<?php endif;
page_end();
