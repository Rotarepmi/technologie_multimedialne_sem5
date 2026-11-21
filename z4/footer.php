<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
// 16c: stan alarmu dźwiękowego pokazany standardową ikoną SVG (Bootstrap Icons)
$soundOn = !empty($_SESSION['sound_on']);
?>
	<footer class="page-footer mt-auto bg-light border-primary">
		<div class="container-fluid d-flex justify-content-between">
			<span>Mandra - monitorowanie usług w sieciach IP</span>
			<a href="ustawienia.php" class="text-decoration-none" title="Zmień ustawienia alarmu">
				<img src="media/icons/<?= $soundOn ? 'volume-up-fill' : 'volume-mute-fill' ?>.svg" width="14" height="14" alt="">
				Alarm dźwiękowy: <?= $soundOn ? 'włączony' : 'wyłączony' ?>
			</a>
		</div>
	</footer>
