<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
if (session_status() === PHP_SESSION_NONE) { // garantir que só acontece uma vez
    session_start();
}

// Função para tratar o login
function handleLogin($conn)
{
    $msg = "";
    $email_or_username = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    $captchaSuccess = validateCaptcha($captcha);
    if (!$captchaSuccess) {
        $msg = "Erro: CAPTCHA inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id, password_hash, valid, username FROM user WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email_or_username, $email_or_username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $userId = null;
            $hash = '';
            $valid = 0;
            $username = '';
            $stmt->bind_result($userId, $hash, $valid, $username);
            $stmt->fetch();

            if ($valid === 1 && password_verify($password, $hash)) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                header("Location: ../../index.php");
                exit;
            } elseif ($valid !== 1) {
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
    return $msg;
}

// Função para registar users
function handleRegister($conn)
{
    $msg = "";
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    $captchaSuccess = validateCaptcha($captcha);
    if (!$captchaSuccess) {
        $msg = "Erro: CAPTCHA inválido.";
    } else {
        if (!hasUser($conn, $username, $email)) {
            $msg = "Erro: Nome de utilizador ou email já existe.";
            return $msg;
        }

        // Inserir novo utilizador 
        $token = bin2hex(random_bytes(32)); // Gerar token de verificação
        $stmt = $conn->prepare("INSERT INTO user (username, full_name, email, password_hash, valid, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
        $valid = 0; // Conta não validada por padrão
        $stmt->bind_param("ssssis", $username, $full_name, $email, $password_hash, $valid, $token);

        if ($stmt->execute()) {
            $emailSend = sendVerificationEmail($email, $full_name, $token);
            if ($emailSend) {
                $msg = "Registo feito. Verifica o teu email."; // REGISTO BEM EFETUADO
            } else {
                $msg = "Erro ao enviar email";
            }
        } else {
            $msg = "Erro ao registar: " . $conn->error;
        }
        $stmt->close();
    }
    return $msg;
}

function enterViaCodigo($conn, $code)
{
    $albumId = "";
    $stmt = $conn->prepare("SELECT id FROM album WHERE access_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->bind_result($albumId);

    if ($stmt->fetch()) {
        $_SESSION['guest_album'] = $albumId;
        header("Location: ../album/album.php?id=$albumId");
        exit();
    }
    $stmt->close();
    return "Código inválido.";
}

function verify()
{
    $conn = db_connect(); // verificar se nao dá erro assim.
    $token = $_GET['token'] ?? null;

    if ($token) {
        $stmt = $conn->prepare("SELECT id FROM user WHERE verification_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userId);
            $stmt->fetch();

            $update = $conn->prepare("UPDATE user SET valid = 1, verification_token = NULL WHERE id = ?");
            $update->bind_param("i", $userId);
            $update->execute();
            $update->close();

            return true;
        }

        $stmt->close();
    }
    $conn->close();
    return false;
}

function validateCaptcha($captcha)
{
    $captchaSecret = '6Ld8g0UrAAAAAFWEtty29mcyu5t8min6sWmU-Ug3';
    $captchaResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$captchaSecret&response=$captcha");
    $captchaSuccess = json_decode($captchaResponse, true);
    return $captchaSuccess['success'] ?? false;
}

function sendVerificationEmail($email, $full_name, $token)
{
    $xml = simplexml_load_file(__DIR__ . '/../config.xml') or die("No XML file found");
    $mailjetApiKey = (string) $xml->phpMailer->APIKey;
    $mailjetApiSecret = (string) $xml->phpMailer->APISecret;
    $mailjetFromEmail = (string) $xml->phpMailer->fromEmail;
    $mailjetFromName = (string) $xml->phpMailer->fromName;
    $server = (string) $xml->server->host;

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'in-v3.mailjet.com';
    $mail->SMTPAuth = true;
    $mail->Username = $mailjetApiKey;
    $mail->Password = $mailjetApiSecret;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom($mailjetFromEmail, $mailjetFromName);
    $mail->addAddress($email, $full_name);
    $mail->Subject = 'Verifica a tua conta';
    $mail->Body = "Clica no link para verificar a conta:\n\n$server/verify.php?token=$token";

    return $mail->send(); // True se tiver sido enviado
}

function deleteUser()
{
    $conn = db_connect();
    $userId = $_SESSION['user_id'];

    if (isset($_POST['delete_account'])) {
        $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        session_destroy();
        header("Location: ../view/auth/login.php");
        return "Conta apagada com sucesso.";
    }
    return "Erro ao apagar conta.";
}
