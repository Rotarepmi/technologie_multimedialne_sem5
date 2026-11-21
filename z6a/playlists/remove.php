<?php
// Usunięcie utworu z własnej playlisty (sam utwór zostaje w portalu)
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/music.php';
require_post_csrf();

$idpldb = (int)($_POST['idpldb'] ?? 0);
$link = db_connect();
$row = query_one($link,
  'SELECT pd.idpl, s.title FROM playlistdatabase pd
   JOIN playlistname pn ON pn.idpl = pd.idpl JOIN song s ON s.ids = pd.ids
   WHERE pd.idpldb = ? AND pn.idu = ?', 'ii', $idpldb, current_user_id());
if ($row && execute($link, 'DELETE FROM playlistdatabase WHERE idpldb = ?', 'i', $idpldb) === 1) {
  flash('success', "Usunięto „{$row['title']}” z playlisty.");
} else {
  flash('danger', 'Nie udało się usunąć utworu z playlisty.');
}
mysqli_close($link);
redirect($row ? 'play.php?idpl=' . (int)$row['idpl'] : 'index.php');
