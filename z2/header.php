<?php

declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();
$username = htmlspecialchars($_SESSION['username'] ?? '');
$avatar = $_SESSION['avatar'] ?? null;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<header class="p-2 border-bottom bg-white fixed-top shadow-sm">
	<div class="container-fluid">
		<div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
			<a href="index.php" class="d-flex align-items-center mb-2 mb-lg-0 me-lg-3 link-dark text-decoration-none">
				<span class="fs-5 fw-semibold">Mandra</span>
			</a>

			<ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
				<li class="nav-item dropdown">
					<a href="#" class="nav-link px-2 link-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Polecenia 1.x</a>
					<ul class="dropdown-menu text-small shadow">
						<li><a class="dropdown-item" href="polecenie1_1.php"><img src="media/menu_icons/info.svg" height="18" alt=""> Polecenie 1.1</a></li>
						<li><a class="dropdown-item" href="polecenie1_2.php"><img src="media/menu_icons/help.svg" height="18" alt=""> Polecenie 1.2</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a href="#" class="nav-link px-2 link-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Polecenia 2.x</a>
					<ul class="dropdown-menu text-small shadow">
						<li><a class="dropdown-item" href="polecenie2_1.php">Polecenie 2.1</a></li>
						<li><a class="dropdown-item" href="polecenie2_2.php">Polecenie 2.2</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a href="#" class="nav-link px-2 link-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Polecenia 3.x</a>
					<ul class="dropdown-menu text-small shadow">
						<li><a class="dropdown-item" href="polecenie3_1.php">Polecenie 3.1</a></li>
						<li><a class="dropdown-item" href="polecenie3_2.php">Polecenie 3.2</a></li>
						<li><a class="dropdown-item" href="polecenie3_3.php">Polecenie 3.3</a></li>
					</ul>
				</li>
			</ul>

			<div class="dropdown text-end">
				<a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
					<?php if ($avatar): ?>
						<img src="<?= htmlspecialchars($avatar) ?>" alt="<?= $username ?>" width="32" height="32" class="rounded-circle object-fit-cover">
					<?php else: ?>
						<i class="fa fa-user-circle fa-2x text-secondary"></i>
					<?php endif; ?>
				</a>
				<ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser">
					<li>
						<h6 class="dropdown-header">Zalogowany jako <strong><?= $username ?></strong></h6>
					</li>
					<li>
						<hr class="dropdown-divider">
					</li>
					<li><a class="dropdown-item" href="logout.php"><i class="fa fa-sign-out"></i> Wyloguj</a></li>
				</ul>
			</div>
		</div>
	</div>
</header>