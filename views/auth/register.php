<?php
require_once '../../libs/vendor/autoload.php';
require_once '../../controllers/controlador_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = db_connect();
  $msg = handleRegister($conn);
  $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <title>Registo</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- reCAPTCHA -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <!-- CSS -->
  <link rel="stylesheet" href="../../assets/styles/register.css">
</head>

<body class="d-flex min-vh-100 align-items-stretch">
  <div class="row flex-grow-1 w-100 g-0">
    <!-- Imagem à esquerda -->
    <div class="col-md-6 left-side d-flex justify-content-center align-items-center">
      <div class="text-white fw-bold fs-1 text-center">Create your<br>account</div>
    </div>

    <!-- Formulário à direita -->
    <div class="col-md-6 right-side d-flex align-items-center justify-content-center p-5">
      <div class="register-box w-100">
        <h2 class="text-center mb-4">REGISTAR</h2>

        <?php if (isset($msg)) echo "<div class='alert alert-danger text-center'><strong>$msg</strong></div>"; ?>

        <form id="formRegisto" action="" method="POST">
          <input type="text" id="username" name="username" class="form-control mb-3" placeholder="Nome de utilizador" required>
          <input type="text" name="full_name" class="form-control mb-3" placeholder="Nome completo" required>
          <input type="email" id="email" name="email" class="form-control mb-3" placeholder="Email" required>
          <input type="password" id="password" name="password" class="form-control mb-3" placeholder="Password" required>
          <div class="d-flex justify-content-center mb-3">
            <div class="g-recaptcha" data-sitekey="6Ld8g0UrAAAAAA0aryyBRoONa67Ec5nXegiz5ymn"></div>
          </div>
          <button type="submit" class="btn btn-info w-100 shadow">Criar Conta</button>
        </form>

        <div class="text-center mt-3">
          Já tens conta? <a href="login.php">Faz login</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("formRegisto").addEventListener("submit", function(e) {
      const username = document.getElementById("username").value;
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;

      // Validação de espaços no user/email
      if (/\s/.test(username)) {
        alert("O nome de utilizador não pode conter espaços.");
        e.preventDefault(); // impede envio do formulário
      }

      // Validação de email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        alert("Email inválido.");
        e.preventDefault(); // Impede envio do formulário
        return;
      }

      // Tamanho da password
      if (password.length < 5) {
        alert("A password deve ter pelo menos 5 caracteres.");
        e.preventDefault();
      }
    });
  </script>
</body>

</html>