<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mandra</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="../assets/js/visit.js"></script>
  <link href="../assets/css/form-validation.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
  <main>
    <div class="py-5 text-center">
      <i class="bi bi-person-plus-fill text-success d-block mb-3" style="font-size: 4rem"></i>
      <h2>Rejestracja</h2>
      <p class="lead">Załóż konto - na serwerze zostanie utworzony Twój katalog na utwory audio.</p>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <form class="needs-validation" method="post" action="add.php" enctype="multipart/form-data" novalidate>
          <div class="row g-3">
            <div class="col-12">
              <label for="username" class="form-label">Login</label>
              <div class="input-group has-validation">
                <span class="input-group-text">@</span>
                <input type="text" class="form-control" id="username" name="user" minlength="3" maxlength="20" pattern="[A-Za-z0-9_\-]{3,20}" placeholder="Login" required>
                <div class="invalid-feedback">Login: 3-20 znaków - litery bez polskich znaków, cyfry, _ lub -.</div>
              </div>
            </div>

            <div class="col-sm-6">
              <label for="pass" class="form-label">Hasło</label>
              <input type="password" class="form-control" id="pass" name="pass" maxlength="20" required>
              <div class="invalid-feedback">Hasło jest wymagane.</div>
            </div>

            <div class="col-sm-6">
              <label for="pass_confirm" class="form-label">Potwierdź hasło</label>
              <input type="password" class="form-control" id="pass_confirm" name="pass_confirm" maxlength="20" required>
              <div class="invalid-feedback">Potwierdź hasło.</div>
            </div>

            <div class="col-12">
              <label for="avatar" class="form-label">Avatar <span class="text-muted">(opcjonalnie)</span></label>
              <input type="file" class="form-control" id="avatar" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp">
              <small class="text-muted">PNG/JPG/GIF/WEBP, max 2 MB. Bez pliku zostanie użyta domyślna ikona.</small>
            </div>
          </div>

          <hr class="my-4">

          <button class="w-100 btn btn-primary btn-lg" type="submit">Zarejestruj</button>
          <p class="mt-3 text-center">Masz już konto? <a href="login.php">Zaloguj się</a></p>
        </form>
      </div>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
<script src="../assets/js/form-validation.js"></script>
</body>
</html>
