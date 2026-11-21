<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
require_once 'layout.php';
require_once 'monitor.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $_SESSION['sound_on'] = isset($_POST['sound_on']);
  $sound = (int)($_POST['sound'] ?? 1);
  $_SESSION['sound'] = isset(SOUNDS[$sound]) ? $sound : 1;
  flash('success', 'Zapisano ustawienia alarmu dźwiękowego.');
  redirect('ustawienia.php');
}

$current = $_SESSION['sound'] ?? 1;
page_start('Alarm dźwiękowy');
?>
<div class="row">
  <div class="col-md-8 col-lg-5">
    <form method="post" class="card card-body shadow-sm">
      <p class="small text-muted">Alarm odtwarza się, gdy awaria którejś usługi trwa ponad <?= SOUND_AFTER ?> s.</p>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="sound_on" name="sound_on" <?= !empty($_SESSION['sound_on']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="sound_on">Alarm dźwiękowy włączony</label>
      </div>
      <fieldset class="mb-3">
        <legend class="form-label fs-6">Rodzaj dźwięku</legend>
        <?php foreach (SOUNDS as $id => $s): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <input class="form-check-input mt-0" type="radio" name="sound" id="sound<?= $id ?>" value="<?= $id ?>" <?= $id === $current ? 'checked' : '' ?>>
            <label class="form-check-label flex-grow-1" for="sound<?= $id ?>"><?= htmlspecialchars($s['label']) ?></label>
            <button type="button" class="btn btn-outline-secondary btn-sm py-0" onclick="new Audio('<?= $s['file'] ?>').play()">▶ Odsłuchaj</button>
          </div>
        <?php endforeach; ?>
      </fieldset>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Zapisz</button>
        <a href="<?= home_url() ?>" class="btn btn-outline-secondary">Powrót do monitoringu</a>
      </div>
    </form>
  </div>
</div>
<?php
page_end();
