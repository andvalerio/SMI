<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../../libs/vendor/autoload.php';
require_once '../../includes/db.php';
// Carregar config.xml para as credenciais do Mailjet
$xml = simplexml_load_file(__DIR__ . '/../../config.xml') or die("No XML file found");
$mailjetApiKey = (string) $xml->phpMailer->APIKey;
$mailjetApiSecret = (string) $xml->phpMailer->APISecret;
$mailjetFromEmail = (string) $xml->phpMailer->fromEmail;
$mailjetFromName = (string) $xml->phpMailer->fromName;
$server = (string) $xml->server->host;


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
      } else {
        $stmt->close();
        // Inserir novo utilizador 
        $token = bin2hex(random_bytes(32)); // Gerar token de verificação
        $stmt = $conn->prepare("INSERT INTO user (username, full_name, email, password_hash, valid, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
        $valid = 0; // Conta não validada por padrão
        $stmt->bind_param("ssssis", $username, $full_name, $email, $password_hash, $valid, $token);
          
        if ($stmt->execute()) {
          // Enviar email de verificação
          $mail = new PHPMailer\PHPMailer\PHPMailer();
          $mail->isSMTP();
          $mail->Host = 'in-v3.mailjet.com';
          $mail->SMTPAuth = true;
          $mail->Username = $mailjetApiKey;
          $mail->Password = $mailjetApiSecret;
          $mail->SMTPSecure = 'tls';
          $mail->Port = 587;
          $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
          ];

          $mail->setFrom($mailjetFromEmail, $mailjetFromName);
          $mail->addAddress($email, $full_name);
          $mail->Subject = 'Verifica a tua conta';
          $mail->Body = "Clica no link para verificar a conta:\n\n$server/views/auth/verify.php?token=$token";

          if ($mail->send()) {
            $msg = "Registo feito. Verifica o teu email.";
          } else {
            $msg = "Erro ao enviar email: " . $mail->ErrorInfo;
          }
              
        } else {
          $msg = "Erro ao registar: " . $conn->error;
        }
        $stmt->close();
      }
    }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <title>Registo</title>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <link rel="stylesheet" href="../../assets/styles/register.css">
</head>

<body>
  <div class="container">
    <div class="left-side">
      Create your<br>account
    </div>
    <div class="right-side">
      <div class="register-box">
        <h2>REGISTAR</h2>
        <?php if (isset($msg)) echo "<p class='msg'><strong>$msg</strong></p>"; ?>
        <form action="" method="POST">
          <input type="text" name="username" placeholder="Nome de utilizador" required>
          <input type="text" name="full_name" placeholder="Nome completo" required>
          <input type="email" name="email" placeholder="Email" required>
          <input type="password" name="password" placeholder="Password" required>
          <div class="g-recaptcha" data-sitekey="6Ld8g0UrAAAAAA0aryyBRoONa67Ec5nXegiz5ymn"></div>
          <input type="submit" value="Criar Conta">
        </form>
        <div class="login-link">
          Já tens conta? <a href="login.php">Faz login</a>
        </div>
      </div>
    </div>
  </div>
</body>

</html>