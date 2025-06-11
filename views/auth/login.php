<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../../controllers/AuthController.php'; // para login
session_start();

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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link rel="stylesheet" href="../../assets/styles/login.css">
</head>

<body>
    <div class="container">
        <div class="left-side">
            Welcome<br>back . . .
        </div>
        <div class="right-side">
            <div class="login-box">
                <h2>LOGIN</h2>
                <?php if (isset($msg)) echo "<p class='msg'><strong>$msg</strong></p>"; ?>
                <form action="" method="POST">
                    <input type="text" name="email" placeholder="Email ou nome de utilizador:" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <div class="g-recaptcha" data-sitekey="6Ld8g0UrAAAAAA0aryyBRoONa67Ec5nXegiz5ymn"></div>
                    <input type="submit" value="Entrar">
                </form>
                <div class="register-link">
                    Ainda não tens conta? <a href="register.php">Regista-te</a><br>
                    <a href="#">Login usando código do álbum</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>