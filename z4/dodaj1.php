<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
require_once 'layout.php';
require_once 'monitor.php';

page_start('Dodaj usługę do monitorowania');
?>
<div class="row">
  <div class="col-md-8 col-lg-5">
    <form method="post" action="dodaj2.php" class="card card-body shadow-sm">
      <div class="mb-3">
        <label for="host" class="form-label">Host (nazwa domenowa lub IP)</label>
        <input type="text" class="form-control" id="host" name="host" maxlength="253" placeholder="np. fb.com" required autofocus>
      </div>
      <div class="mb-3">
        <label for="port" class="form-label">Port</label>
        <input type="number" class="form-control" id="port" name="port" min="1" max="65535" placeholder="np. 80" list="common-ports" required>
        <datalist id="common-ports">
          <?php foreach (SERVICE_TYPES as $def): foreach ($def['ports'] as $p): ?>
            <option value="<?= $p ?>"><?= htmlspecialchars($def['label']) ?></option>
          <?php endforeach; endforeach; ?>
        </datalist>
        <div class="form-text">Port identyfikuje usługę - na jednym hoście może działać kilka usług.</div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Dodaj</button>
        <a href="<?= home_url() ?>" class="btn btn-outline-secondary">Powrót do monitoringu</a>
      </div>
    </form>
  </div>
</div>
<?php
page_end();
