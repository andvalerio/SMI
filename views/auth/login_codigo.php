<?php
require_once '../../includes/db.php';
require_once '../../includes/session.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['access_code']);
    $conn = db_connect();

    $stmt = $conn->prepare("SELECT id FROM album WHERE access_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->bind_result($albumId);

    if ($stmt->fetch()) {
        $_SESSION['guest_album'] = $albumId;
        header("Location: ../album/album.php?id=$albumId");
        exit();
    } else {
        $error = "Código inválido.";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Entrar com Código</title>
    <link rel="stylesheet" href="../../assets/styles/login.css">
</head>
<body>
    <div class="container">
        <div class="left-side">
            Só queres ver o álbum?
        </div>
        <div class="right-side">
            <div class="login-box">
                <h2>Entrar com Código</h2>
                <?php if ($error): ?>
                    <p class="msg"><strong><?= htmlspecialchars($error) ?></strong></p>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="access_code" placeholder="Código do álbum" required>
                    <input type="submit" value="Entrar">
                </form>
                <div class="register-link">
                    <a href="login.php">Voltar ao login normal</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
