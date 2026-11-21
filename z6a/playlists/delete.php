<?php
// Usuwanie własnej playlisty (ON DELETE CASCADE usuwa jej wpisy z playlistdatabase; utwory zostają)
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_post_csrf();

$idpl = (int)($_POST['idpl'] ?? 0);
$link = db_connect();
$pl = own_playlist($link, $idpl, current_user_id());
if ($pl && execute($link, 'DELETE FROM playlistname WHERE idpl = ?', 'i', $idpl) === 1) {
  flash('success', "Usunięto playlistę „{$pl['name']}”.");
} else {
  flash('danger', 'Nie udało się usunąć playlisty.');
}
mysqli_close($link);
redirect('index.php');
