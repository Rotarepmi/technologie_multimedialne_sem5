<?php
declare(strict_types=1);

// $bsTable = true dołącza bibliotekę Bootstrap Table (sortowanie, filtrowanie, paginacja tabeli)
function page_start(string $title, bool $bsTable = false): void
{
  $flash = $_SESSION['flash'] ?? null;
  $warning = $_SESSION['security_warning'] ?? null;
  unset($_SESSION['flash'], $_SESSION['security_warning']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="MyCloud - dysk sieciowy">
	<meta name="author" content="Jakub Mandra">
	<title>Mandra</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<?php if ($bsTable): ?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
	<?php endif; ?>
	<link rel="stylesheet" type="text/css" href="../assets/css/twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<?php if ($bsTable): ?>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/locale/bootstrap-table-pl-PL.min.js"></script>
	<?php endif; ?>
	<script type="text/javascript" src="../assets/js/twoj_js.js"></script>
	<script type="text/javascript" src="../assets/js/visit.js"></script>
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<?php if ($warning): /* 6b-iv: ostrzeżenie po poprawnym logowaniu, które nastąpiło po blokadzie konta */ ?>
					<div class="alert alert-danger alert-dismissible fade show mt-3 text-danger fw-semibold" role="alert">
						<i class="bi bi-shield-exclamation"></i>
						Uwaga! Na Twoje konto wykonano 3 błędne próby logowania i zostało ono czasowo zablokowane.
						Ostatnie błędne logowanie: <?= htmlspecialchars($warning['datetime']) ?>, z adresu IP <?= htmlspecialchars($warning['ip']) ?>.
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
					</div>
				<?php endif; ?>
				<h1 class="h4 my-3"><?= htmlspecialchars($title) ?></h1>
				<?php if ($flash): ?>
					<div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
						<?= htmlspecialchars($flash['message']) ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
					</div>
				<?php endif; ?>
<?php
}

function page_end(): void
{
?>
			</div>
		</section>
	</main>
	<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
<?php
}
