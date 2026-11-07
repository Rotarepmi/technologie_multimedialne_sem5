<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();
$username = htmlspecialchars($_SESSION['username'] ?? '');
$avatar = $_SESSION['avatar'] ?? null;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<header>
<div class="container">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mt-0 mb-0 ms-0 me-0 pt-0 pb-0 ps-0 pe-0">
	<div class="container-fluid">
	
		<div class="collapse navbar-collapse" id="main_nav">
			<ul class="navbar-nav">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 1.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie1_1.php"> <img src="media/menu_icons/info.svg" height="18"> Polecenie 1.1 </a></li>
						<li><a class="dropdown-item" href="polecenie1_2.php"> <img src="media/menu_icons/help.svg" height="18"> Polecenie 1.2 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 2.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie2_1.php"> Polecenie 2.1 </a></li>
						<li><a class="dropdown-item" href="polecenie2_2.php"> Polecenie 2.2 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 3.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie3_1.php"> Polecenie 3.1 </a></li>
						<li><a class="dropdown-item" href="polecenie3_2.php"> Polecenie 3.2 </a></li>
						<li><a class="dropdown-item" href="polecenie3_3.php"> Polecenie 3.3 </a></li>
					</ul>
				</li>
			</ul> 
			<ul class="navbar-nav ms-auto">
				<li class="nav-item">
					<span class="navbar-text text-white me-3">
						<?php if ($avatar): ?>
							<img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" width="28" height="28" class="rounded-circle">
						<?php else: ?>
							<i class="fa fa-user-circle fa-lg"></i>
						<?php endif; ?>
						<?= $username ?>
					</span>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="logout.php"><i class="fa fa-sign-out"></i> Wyloguj</a>
				</li>
			</ul>
		</div> 
		
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav"  aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		
	</div> 
</nav>		
		
</div>
</header>