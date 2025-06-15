<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../../controllers/AuthController.php';

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    $msg = handleLogin($conn);
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <title>Login</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- reCAPTCHA -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <!-- CSS personalizado -->
  <link rel="stylesheet" href="../../assets/styles/login.css">

</head>

<body class="d-flex min-vh-100 align-items-stretch">
  <div class="row flex-grow-1 w-100 g-0">
    <!-- Imagem à esquerda -->
    <div class="col-md-6 left-side d-flex justify-content-center align-items-center">
      <div class="text-white fw-bold fs-1 text-center">Welcome<br>back . . .</div>
    </div>

    <!-- Formulário à direita -->
    <div class="col-md-6 right-side d-flex align-items-center justify-content-center">
      <div class="login-box">
        <h2 class="text-center mb-4">LOGIN</h2>

        <?php if (isset($msg)) echo "<div class='alert alert-danger text-center'><strong>$msg</strong></div>"; ?>

        <form action="" method="POST">
          <input type="text" name="email" class="form-control" placeholder="Email ou nome de utilizador:" required>
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="g-recaptcha mb-3" data-sitekey="6Ld8g0UrAAAAAA0aryyBRoONa67Ec5nXegiz5ymn"></div>
          <button type="submit" class="btn btn-login">Entrar</button>
        </form>

        <div class="text-center mt-3">
          Ainda não tens conta? <a href="register.php">Regista-te</a><br>
          <a href="login_codigo.php">Login usando código do álbum</a>
        </div>
      </div>
    </div>
  </div>
</body>

</body>
</html>
