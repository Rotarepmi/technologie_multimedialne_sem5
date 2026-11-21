<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mandra - logowanie</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link href="signin.css" rel="stylesheet">
  <script src="visit.js"></script>
</head>
<body class="text-center">
<main class="form-signin">
  <form method="post" action="weryfikuj.php">
    <i class="fa fa-user-circle fa-4x text-primary mb-4"></i>
    <h1 class="h3 mb-3 fw-normal">Zaloguj się</h1>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger">Błędny login lub hasło.</div>
    <?php elseif (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Konto utworzone. Możesz się zalogować.</div>
    <?php endif; ?>

    <div class="form-floating">
      <input type="text" class="form-control" id="floatingInput" name="user" maxlength="20" placeholder="Login" required>
      <label for="floatingInput">Login</label>
    </div>
    <div class="form-floating">
      <input type="password" class="form-control" id="floatingPassword" name="pass" maxlength="20" placeholder="Hasło" required>
      <label for="floatingPassword">Hasło</label>
    </div>

    <button class="w-100 btn btn-lg btn-primary" type="submit">Zaloguj</button>
    <p class="mt-3">Nie masz konta? <a href="rejestruj.php">Zarejestruj się</a></p>
    <p class="mt-5 mb-3 text-muted">&copy; Mandra</p>
  </form>
</main>
</body>
</html>
