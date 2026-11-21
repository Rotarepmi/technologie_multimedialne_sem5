<?php
declare(strict_types=1);

function page_start(string $title): void
{
  $flash = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Monitorowanie usług w sieciach IP">
	<meta name="author" content="Jakub Mandra">
	<title>Mandra - <?= htmlspecialchars($title) ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script type="text/javascript" src="twoj_js.js"></script>
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
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
	<?php require 'footer.php'; ?>
</body>
</html>
<?php
}
