<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../../controllers/AuthController.php';


$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['access_code']);
    $conn = db_connect();
    $error = enterViaCodigo($conn, $code);
    /*
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
    */

    $conn->close();
}
?>
<?php
// Supondo que $error esteja definido antes do HTML (p. ex. $error = "Código inválido";)
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Entrar com Código</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex min-vh-100 align-items-stretch">
    <div class="row flex-grow-1 w-100 g-0">

        <!-- Lado esquerdo (imagem ou fundo com texto) -->
        <!-- Lado esquerdo com imagem de fundo e texto -->
        <div class="col-md-6 d-none d-md-flex justify-content-center align-items-center text-white"
            style="background-image: url('../../assets/styles/imgs/login.jpg'); background-size: cover; background-position: center;">
            <h1 class="text-center px-4 fw-bold">Só queres ver<br>o álbum?</h1>
        </div>


        <!-- Lado direito (formulário) -->
        <div class="col-md-6 d-flex align-items-center justify-content-center p-5">
            <div class="w-100" style="max-width: 400px;">
                <h2 class="text-center mb-4">Entrar com Código</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center">
                        <strong><?= htmlspecialchars($error) ?></strong>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <input type="text" name="access_code" class="form-control" placeholder="Código do álbum" required>
                    </div>
                    <button type="submit" class="btn btn-info w-100 shadow">Entrar</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php">Voltar ao login normal</a>
                </div>
            </div>
        </div>
    </div>

</body>

</html>