<?php
declare(strict_types=1);  /* Ta linia musi być pierwsza */
require_once 'auth.php';
require_admin();
require_once 'db.php';
require_once 'layout.php';

$link = db_connect();
// 15e.ii: na liście rozwijanej tylko użytkownicy z worker = true
$workers = mysqli_fetch_all(mysqli_query($link, 'SELECT id, username FROM users WHERE worker = TRUE ORDER BY username'), MYSQLI_ASSOC);
$users = mysqli_fetch_all(mysqli_query($link,
  "SELECT u.id, u.username, u.worker, COUNT(d.id) AS services
   FROM users u LEFT JOIN domeny d ON d.user_id = u.id
   WHERE u.username <> 'admin' GROUP BY u.id ORDER BY u.username"), MYSQLI_ASSOC);
mysqli_close($link);

page_start('Personel - przypisywanie usług');
?>
<div class="row g-4 mb-5">
  <div class="col-lg-5">
    <form method="post" action="staff2.php" class="card card-body shadow-sm">
      <input type="hidden" name="action" value="assign">
      <h2 class="h6 mb-3">Nowa usługa dla pracownika</h2>
      <div class="mb-3">
        <label for="host" class="form-label">Host</label>
        <input type="text" class="form-control" id="host" name="host" maxlength="253" placeholder="np. youtube.com" required>
      </div>
      <div class="mb-3">
        <label for="port" class="form-label">Port</label>
        <input type="number" class="form-control" id="port" name="port" min="1" max="65535" placeholder="np. 443" required>
      </div>
      <div class="mb-3">
        <label for="worker" class="form-label">Pracownik odpowiedzialny</label>
        <select class="form-select" id="worker" name="worker_id" required <?= $workers ? '' : 'disabled' ?>>
          <?php if (!$workers): ?>
            <option>Brak pracowników - oznacz kogoś obok</option>
          <?php endif; ?>
          <?php foreach ($workers as $w): ?>
            <option value="<?= (int)$w['id'] ?>"><?= htmlspecialchars($w['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" <?= $workers ? '' : 'disabled' ?>>Przypisz</button>
        <a href="admin.php" class="btn btn-outline-secondary">Powrót do monitoringu</a>
      </div>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white"><h2 class="h6 mb-0">Użytkownicy</h2></div>
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Login</th><th>Usługi</th><th>Pracownik</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= (int)$u['services'] ?></td>
            <td><?= $u['worker'] ? '<span class="badge bg-success">tak</span>' : '<span class="badge bg-secondary">nie</span>' ?></td>
            <td class="text-end">
              <form method="post" action="staff2.php" class="d-inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button class="btn btn-outline-secondary btn-sm py-0"><?= $u['worker'] ? 'Odbierz' : 'Nadaj' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
page_end();
