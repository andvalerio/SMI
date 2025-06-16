<?php
require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../../controllers/controlador_auth.php';


$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['access_code']);
    $conn = db_connect();
    $error = enterViaCodigo($conn, $code);
    $conn->close();
}
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

                <form id="formCodigo" method="POST">
                    <div class="mb-3">
                        <input type="text" id="codigo" name="access_code" class="form-control" placeholder="Código do álbum" required>
                    </div>
                    <button type="submit" class="btn btn-info w-100 shadow">Entrar</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php">Voltar ao login normal</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("formCodigo").addEventListener("submit", function(e) {
            const codigo = document.getElementById("codigo").value;

            // Tamanho da password
            if (codigo.length != 6) {
                alert("A password deve ter 6 caracteres.");
                e.preventDefault();
            }
        });
    </script>
</body>

</html>