<?php
// 9: dns_get_record() - wszystkie rekordy DNS domeny
declare(strict_types=1);
require_once 'auth.php';
require_once 'layout.php';

$domain = trim($_GET['domain'] ?? 'pbs.edu.pl');
page_start('dns_get_record() - rekordy DNS');
?>
<form class="row g-2 mb-3" method="get">
	<div class="col-auto"><input class="form-control form-control-sm" name="domain" value="<?= htmlspecialchars($domain) ?>"></div>
	<div class="col-auto"><button class="btn btn-sm btn-primary">Sprawdź</button></div>
</form>
<?php
$result = @dns_get_record($domain);
print_command_output($result === false ? null : print_r($result, true), "dns_get_record('$domain')");
page_end();
