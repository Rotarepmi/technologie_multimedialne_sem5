<?php
declare(strict_types=1);

// $bsTable = true dołącza bibliotekę Bootstrap Table (sortowanie, filtrowanie, paginacja tabeli)
function page_start(string $title, bool $bsTable = false): void
{
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Geolokalizacja, logowanie wizyt gości portalu">
	<meta name="author" content="Jakub Mandra">
	<title>Mandra - <?= htmlspecialchars($title) ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<?php if ($bsTable): ?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
	<?php endif; ?>
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<?php if ($bsTable): ?>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/locale/bootstrap-table-pl-PL.min.js"></script>
	<?php endif; ?>
	<script type="text/javascript" src="twoj_js.js"></script>
	<script type="text/javascript" src="visit.js"></script>
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<h1 class="h4 my-3"><?= htmlspecialchars($title) ?></h1>
<?php
}

function page_end(): void
{
?>
			</div>
		</section>
	</main>
	<?php require 'footer.php'; ?>
</body>
</html>
<?php
}

// Wynik polecenia systemowego w <pre>; null/false = funkcja zablokowana na hostingu
function print_command_output(string|false|null $output, string $what): void
{
  if ($output === null || $output === false || trim($output) === '') {
    echo '<div class="alert alert-warning">Brak wyniku: ' . htmlspecialchars($what)
      . ' - polecenie jest prawdopodobnie zablokowane na tym hostingu (disable_functions / brak SSH).</div>';
    return;
  }
  echo '<pre class="bg-white border p-2">' . htmlspecialchars($output) . '</pre>';
}

function exec_available(string $fn): bool
{
  $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
  return function_exists($fn) && !in_array($fn, $disabled, true);
}
