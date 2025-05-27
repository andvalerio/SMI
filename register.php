<?php
require_once 'vendor/autoload.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();

    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    $captchaSecret = '6Ld8g0UrAAAAAFWEtty29mcyu5t8min6sWmU-Ug3';
    $captchaResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$captchaSecret&response=$captcha");
    $captchaSuccess = json_decode($captchaResponse, true)['success'];

    if (!$captchaSuccess) {
        $msg = "Erro: CAPTCHA inválido.";
    } else {
        // Verificar se o utilizador já existe
        $stmt = $conn->prepare("SELECT id FROM user WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $msg = "Erro: Nome de utilizador ou email já existe.";
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();
        // Inserir novo utilizador 
        $token = bin2hex(random_bytes(32)); // Gerar token de verificação
        $stmt = $conn->prepare("INSERT INTO user (username, full_name, email, password_hash, valid) VALUES (?, ?, ?, ?, ?)");
        $valid = false; // Conta não validada por padrão
        $stmt->bind_param("ssssb", $username, $full_name, $email, $password_hash, $valid);
        
        if ($stmt->execute()) {
            // Enviar email de verificação
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'teuemail@gmail.com';
            $mail->Password = 'tuapalavrapasseouappkey';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('teuemail@gmail.com', 'Sistema de Álbuns');
            $mail->addAddress($email, $full_name);
            $mail->Subject = 'Verifica a tua conta';
            $mail->Body = "Clica no link para verificar a conta:\n\nhttp://teusite.com/verify.php?token=$token";

            $mail->send();
            $msg = "Registo feito. Verifica o teu email.";
        } else {
            $msg = "Erro ao registar: " . $conn->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!-- Formulário HTML -->
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Registo</title>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <h2>Registar nova conta</h2>
  <?php if (isset($msg)) echo "<p><strong>$msg</strong></p>"; ?>
  <form action="" method="POST">
    <label>Nome de utilizador:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Nome completo:</label><br>
    <input type="text" name="full_name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <div class="g-recaptcha" data-sitekey="6Ld8g0UrAAAAAA0aryyBRoONa67Ec5nXegiz5ymn"></div><br>

    <input type="submit" value="Criar Conta">
  </form>

  <p>Já tens conta? <a href="login.php">Faz login</a></p>
</body>
</html>
