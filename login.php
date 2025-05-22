<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    $captchaSecret = '6Ld8g0UrAAAAAFWEtty29mcyu5t8min6sWmU-Ug3';
    $captchaResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$captchaSecret&response=$captcha");
    $captchaSuccess = json_decode($captchaResponse, true)['success'];

    if (!$captchaSuccess) {
        $msg = "Erro: CAPTCHA inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id, password_hash, valid, username FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($userId, $hash, $valid, $username);
            $stmt->fetch();

            if ($valid === '1' && password_verify($password, $hash)) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit;
            } elseif ($valid !== '1') {
                $msg = "A tua conta ainda não está verificada.";
            } else {
                $msg = "Password incorreta.";
            }
        } else {
            $msg = "Email não encontrado.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <h2>Entrar</h2>
  <?php if (isset($msg)) echo "<p><strong>$msg</strong></p>"; ?>
  <form action="" method="POST">
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <div class="g-recaptcha" data-sitekey="6Ld8g0UrAAAAAA0aryyBRoONa67Ec5nXegiz5ymn"></div><br>

    <input type="submit" value="Entrar">
  </form>

  <p>Ainda não tens conta? <a href="register.php">Regista-te</a></p>
</body>
</html>
