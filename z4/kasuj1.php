<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
require_once 'db.php';
require_once 'layout.php';

// lista usług do podpowiedzi - admin widzi wszystkie, użytkownik tylko swoje
$link = db_connect();
if (is_admin()) {
  $result = mysqli_query($link, 'SELECT DISTINCT host, port FROM domeny ORDER BY host, port');
} else {
  $stmt = mysqli_prepare($link, 'SELECT host, port FROM domeny WHERE user_id = ? ORDER BY host, port');
  mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
}
$services = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_close($link);

page_start('Usuń usługę z monitorowania');
?>
<div class="row">
  <div class="col-md-8 col-lg-5">
    <form method="post" action="kasuj2.php" class="card card-body shadow-sm">
      <div class="mb-3">
        <label for="host" class="form-label">Host</label>
        <input type="text" class="form-control" id="host" name="host" maxlength="253" list="my-hosts"
               value="<?= htmlspecialchars($_GET['host'] ?? '') ?>" required autofocus>
        <datalist id="my-hosts">
          <?php foreach (array_unique(array_column($services, 'host')) as $h): ?>
            <option value="<?= htmlspecialchars($h) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="mb-3">
        <label for="port" class="form-label">Port</label>
        <input type="number" class="form-control" id="port" name="port" min="1" max="65535"
               value="<?= htmlspecialchars($_GET['port'] ?? '') ?>" required>
        <div class="form-text">Trzeba podać host i port - na jednym hoście może być monitorowanych kilka usług.</div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">Usuń</button>
        <a href="<?= home_url() ?>" class="btn btn-outline-secondary">Powrót do monitoringu</a>
      </div>
    </form>
  </div>
  <?php if ($services): ?>
    <div class="col-md-4 col-lg-3 mt-3 mt-md-0">
      <div class="small text-muted mb-1"><?= is_admin() ? 'Wszystkie usługi' : 'Twoje usługi' ?>:</div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($services as $s): ?>
          <li class="list-group-item py-1"><?= htmlspecialchars($s['host']) ?>:<?= (int)$s['port'] ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
<?php
page_end();
