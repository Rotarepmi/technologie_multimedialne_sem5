<?php

declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();
if (!isset($_SESSION['loggedin'])) {
	exit(); // navbar tylko po zalogowaniu
}
$username = htmlspecialchars($_SESSION['username'] ?? '');
$avatar = $_SESSION['avatar'] ?? null;
$isAdmin = !empty($_SESSION['is_admin']);
?>
<header class="p-2 border-bottom bg-white fixed-top shadow-sm">
	<div class="container-fluid">
		<div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
			<a href="../music/index.php" class="d-flex align-items-center mb-2 mb-lg-0 me-lg-3 link-dark text-decoration-none">
				<i class="bi bi-music-note-beamed text-success fs-4 me-2"></i>
				<span class="fs-5 fw-semibold">mySpotify</span>
			</a>

			<ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
				<li class="nav-item"><a href="../music/index.php" class="nav-link px-2 link-dark"><i class="bi bi-music-note-list"></i> Moje utwory</a></li>
				<li class="nav-item"><a href="../music/select.php" class="nav-link px-2 link-dark"><i class="bi bi-cloud-upload"></i> Dodaj utwór</a></li>
				<li class="nav-item"><a href="../playlists/index.php" class="nav-link px-2 link-dark"><i class="bi bi-collection-play"></i> Playlisty</a></li>
				<?php if ($isAdmin): ?>
					<li class="nav-item dropdown">
						<a href="#" class="nav-link px-2 link-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-shield-lock"></i> Administrator</a>
						<ul class="dropdown-menu text-small shadow">
							<li><a class="dropdown-item" href="../admin/logowania.php"><i class="bi bi-list-check"></i> Logowania i wizyty (goscieportalu)</a></li>
							<li><a class="dropdown-item" href="../admin/break_ins.php"><i class="bi bi-exclamation-octagon"></i> Próby włamań (break_ins)</a></li>
						</ul>
					</li>
				<?php endif; ?>
			</ul>

			<div class="dropdown text-end">
				<a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
					<?php if ($avatar): ?>
						<img src="<?= htmlspecialchars("../" . $avatar) ?>" alt="<?= $username ?>" width="32" height="32" class="rounded-circle object-fit-cover">
					<?php else: ?>
						<i class="bi bi-person-circle fs-3 text-secondary"></i>
					<?php endif; ?>
				</a>
				<ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser">
					<li>
						<h6 class="dropdown-header">Zalogowany jako <strong><?= $username ?></strong><?= $isAdmin ? ' (administrator)' : '' ?></h6>
					</li>
					<li>
						<hr class="dropdown-divider">
					</li>
					<li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Wyloguj</a></li>
				</ul>
			</div>
		</div>
	</div>
</header>
