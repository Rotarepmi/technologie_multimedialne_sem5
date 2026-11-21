<?php
// 4: netstat w PHP (na wzór netstat.php z https://fam.tuwien.ac.at/~schamane/sysadmin/netstat/):
// czyta tablice gniazd jądra Linuksa z /proc/net/{tcp,tcp6,udp,udp6} i wypisuje porty nasłuchujące
// oraz aktywne połączenia. Rozbudowa: test znanych usług (HTTP 80, HTTPS 443, ...) przez fsockopen().
declare(strict_types=1);
require_once 'auth.php';
require_once 'layout.php';

const TCP_STATES = [
  '01' => 'ESTABLISHED', '02' => 'SYN_SENT', '03' => 'SYN_RECV', '04' => 'FIN_WAIT1', '05' => 'FIN_WAIT2',
  '06' => 'TIME_WAIT', '07' => 'CLOSE', '08' => 'CLOSE_WAIT', '09' => 'LAST_ACK', '0A' => 'LISTEN', '0B' => 'CLOSING',
];

const KNOWN_SERVICES = [
  21 => 'FTP', 22 => 'SSH', 25 => 'SMTP', 53 => 'DNS', 80 => 'HTTP', 110 => 'POP3', 143 => 'IMAP',
  443 => 'HTTPS', 465 => 'SMTPS', 587 => 'SMTP submission', 993 => 'IMAPS', 995 => 'POP3S',
  3306 => 'MySQL/MariaDB', 5432 => 'PostgreSQL', 8080 => 'HTTP alt', 8443 => 'HTTPS alt',
];

// Adres w /proc/net jest zapisany szesnastkowo w kolejności bajtów hosta (little-endian)
function proc_addr(string $hex): string
{
  [$ip, $port] = explode(':', $hex);
  if (strlen($ip) === 8) {
    $addr = inet_ntop(strrev(hex2bin($ip)));
  } else {
    $bin = '';
    foreach (str_split($ip, 8) as $word) {
      $bin .= strrev(hex2bin($word));
    }
    $addr = inet_ntop($bin);
  }
  return $addr . ':' . hexdec($port);
}

function read_proc_net(string $proto): ?array
{
  $lines = @file("/proc/net/$proto", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if ($lines === false) {
    return null;
  }
  $out = [];
  foreach (array_slice($lines, 1) as $line) {
    $f = preg_split('/\s+/', trim($line));
    $state = str_starts_with($proto, 'tcp') ? (TCP_STATES[$f[3]] ?? $f[3]) : ($f[3] === '07' ? 'LISTEN' : 'ESTABLISHED');
    $out[] = ['proto' => $proto, 'local' => proc_addr($f[1]), 'remote' => proc_addr($f[2]), 'state' => $state];
  }
  return $out;
}

page_start('Netstat - porty i usługi serwera');

$all = [];
$hidden = [];
foreach (['tcp', 'tcp6', 'udp', 'udp6'] as $proto) {
  $rows = read_proc_net($proto);
  if ($rows === null) {
    $hidden[] = $proto;
  } else {
    $all = array_merge($all, $rows);
  }
}
?>
<h2 class="h5">Gniazda z /proc/net (<?= count($all) ?>)</h2>
<?php if ($hidden): ?>
	<div class="alert alert-warning">Brak dostępu do /proc/net/<?= htmlspecialchars(implode(', /proc/net/', $hidden)) ?> - hosting ukrywa tablice gniazd (open_basedir, chroot/kontener lub inny system niż Linux).</div>
<?php endif; ?>
<?php if ($all): ?>
	<table class="table table-sm table-bordered small w-auto bg-white">
		<thead class="table-light"><tr><th>Proto</th><th>Adres lokalny</th><th>Adres zdalny</th><th>Stan</th><th>Usługa</th></tr></thead>
		<tbody>
			<?php foreach ($all as $s): $port = (int)substr(strrchr($s['local'], ':'), 1); ?>
				<tr class="<?= $s['state'] === 'LISTEN' ? 'table-success' : '' ?>">
					<td><?= $s['proto'] ?></td>
					<td><?= htmlspecialchars($s['local']) ?></td>
					<td><?= htmlspecialchars($s['remote']) ?></td>
					<td><?= $s['state'] ?></td>
					<td><?= KNOWN_SERVICES[$port] ?? '' ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<h2 class="h5 mt-4">Rozbudowa: test znanych usług przez fsockopen()</h2>
<p class="small text-muted">Netstat widzi tylko gniazda w przestrzeni nazw procesu PHP. Usługi na innych maszynach
	(np. osobny serwer MySQL, load balancer przed HTTP/HTTPS) lub odfiltrowane firewallem są w nim niewidoczne -
	dlatego dodatkowo próbujemy się z nimi połączyć.</p>
<?php
$targets = array_unique(array_filter(['127.0.0.1', $_SERVER['SERVER_NAME'] ?? null, getenv('DB_HOST') ?: null]));
?>
<table class="table table-sm table-bordered small w-auto bg-white">
	<thead class="table-light"><tr><th>Port</th><th>Usługa</th><?php foreach ($targets as $t): ?><th><?= htmlspecialchars($t) ?></th><?php endforeach; ?></tr></thead>
	<tbody>
		<?php foreach (KNOWN_SERVICES as $port => $name): ?>
			<tr>
				<td><?= $port ?></td>
				<td><?= $name ?></td>
				<?php foreach ($targets as $t):
					$fp = @fsockopen($t, $port, $errno, $errstr, 1);
					if ($fp) {
						fclose($fp);
					}
				?>
					<td class="<?= $fp ? 'table-success' : 'text-muted' ?>"><?= $fp ? 'otwarty' : 'zamknięty' ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
page_end();
