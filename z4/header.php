<?php

declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();
$username = htmlspecialchars($_SESSION['username'] ?? '');
$avatar = $_SESSION['avatar'] ?? null;
$isAdmin = !empty($_SESSION['is_admin']);
$home = $isAdmin ? 'admin.php' : 'index.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<header class="p-2 border-bottom bg-white fixed-top shadow-sm">
	<div class="container-fluid">
		<div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
			<a href="<?= $home ?>" class="d-flex align-items-center mb-2 mb-lg-0 me-lg-3 link-dark text-decoration-none">
				<span class="fs-5 fw-semibold">Mandra</span>
			</a>

			<ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
				<li class="nav-item"><a href="<?= $home ?>" class="nav-link px-2 link-dark"><i class="fa fa-heartbeat"></i> Monitoring</a></li>
				<li class="nav-item dropdown">
					<a href="#" class="nav-link px-2 link-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Usługi</a>
					<ul class="dropdown-menu text-small shadow">
						<li><a class="dropdown-item" href="dodaj1.php"><i class="fa fa-plus"></i> Dodaj usługę</a></li>
						<li><a class="dropdown-item" href="kasuj1.php"><i class="fa fa-trash"></i> Usuń usługę</a></li>
					</ul>
				</li>
				<?php if ($isAdmin): ?>
					<li class="nav-item"><a href="staff.php" class="nav-link px-2 link-dark"><i class="fa fa-users"></i> Personel</a></li>
					<li class="nav-item"><a href="goscie.php" class="nav-link px-2 link-dark"><i class="fa fa-globe"></i> Goście portalu</a></li>
				<?php endif; ?>
				<li class="nav-item"><a href="ustawienia.php" class="nav-link px-2 link-dark"><i class="fa fa-volume-up"></i> Alarm</a></li>
				<li class="nav-item dropdown">
					<a href="#" class="nav-link px-2 link-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Skrypty z instrukcji</a>
					<ul class="dropdown-menu text-small shadow">
						<li><a class="dropdown-item" href="test.php">test.php - fsockopen()</a></li>
						<li><a class="dropdown-item" href="index1.php">index1.php - surowa tabela</a></li>
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
						<h6 class="dropdown-header">Zalogowany jako <strong><?= $username ?></strong><?= $isAdmin ? ' (administrator)' : '' ?></h6>
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
