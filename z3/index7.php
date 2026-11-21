<?php
// 9: gethostbyname / gethostbyaddr / $_SERVER['REMOTE_ADDR']
declare(strict_types=1);
require_once 'auth.php';
require_once 'layout.php';

page_start('gethostbyname / gethostbyaddr');
$rows = [
  ["gethostbyname('pbs.edu.pl')", 'IP serwera związanego z nazwą domenową', gethostbyname('pbs.edu.pl')],
  ["\$_SERVER['REMOTE_ADDR']", 'IP routera gościa portalu (Twój publiczny IP)', $_SERVER['REMOTE_ADDR']],
  ["gethostbyaddr('8.8.8.8')", 'nazwa domenowa skojarzona z podanym IP', gethostbyaddr('8.8.8.8')],
  ["gethostbyaddr(\$_SERVER['REMOTE_ADDR'])", 'nazwa domenowa routera gościa portalu', gethostbyaddr($_SERVER['REMOTE_ADDR'])],
];
?>
<table class="table table-sm table-bordered w-auto bg-white">
	<thead class="table-light"><tr><th>Wywołanie</th><th>Opis</th><th>Wynik</th></tr></thead>
	<tbody>
		<?php foreach ($rows as [$call, $desc, $value]): ?>
			<tr><td><code><?= htmlspecialchars($call) ?></code></td><td><?= $desc ?></td><td><?= htmlspecialchars((string)$value) ?></td></tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
page_end();
