<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$conn = db_connect();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erros = "";

    // Sanitizar o nome do álbum
    $album_name = trim($_POST["albumname"]);
    $album_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $album_name);

    if (empty($album_name)) {
        $erros .= "Erro: Nome inválido para o título do álbum. <br>";
    } else {
        $description = $_POST["description"];
        $access_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

        $stmt_album = $conn->prepare($query_add_album);
        $stmt_album->bind_param("sssi", $album_name, $description, $access_code, $userId);

        if ($stmt_album->execute()) {
            $album_id = $stmt_album->insert_id;

            // Atribuir o papel de administrador ao criador do álbum
            $role = "Administrador";
            $stmt_user_album = $conn->prepare($query_add_user_album);
            $stmt_user_album->bind_param("iis", $album_id, $userId, $role);
            $stmt_user_album->execute();
        } else {
            $erros .= "Erro ao criar álbum no banco de dados. <br>";
        }
    }

    if (!empty($erros)) {
        echo "<p>$erros</p>";
    } else {
        header("Location: albuns.php");
        exit;
    }
}
$conn->close();

$notificacao_count = 0;
if (isLoggedIn()) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($notificacao_count);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Criar Álbum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <header class="d-flex justify-content-between align-items-center px-4">
        <strong onclick="location.href='homepage.php'" class="fs-4" style="cursor:pointer">Photo Gallery</strong>

        <div class="d-flex align-items-center gap-2">
            <!-- Botões pa ver albuns -->
            <button class="btn btn-light btn-sm" onclick="location.href='albuns.php'" title="Álbuns">
                <i class="bi bi-images"></i>
            </button>
            <!-- Botões pa ver likes -->
            <button class="btn btn-light btn-sm" onclick="location.href='likes.php'" title="Likes">
                <i class="bi bi-heart-fill"></i>
            </button>

            <!-- Botão de notificações -->
            <button class="btn btn-light btn-sm position-relative" onclick="location.href='notificacoes.php'" title="Notificações">
                <i class="bi bi-bell-fill"></i>
                <?php if ($notificacao_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $notificacao_count ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown de utilizador -->
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Conta">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../auth/account.php">Alterar dados da conta</a></li>
                    <li><a class="dropdown-item" href="../logout.php">Terminar sessão</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow p-4">
                    <h3 class="mb-4 text-center">Criar um Álbum</h3>

                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-danger"><?= $msg ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="albumname" class="form-label">Nome do álbum</label>
                            <input type="text" name="albumname" id="albumname" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição do álbum</label>
                            <textarea name="description" id="description" rows="4" class="form-control" placeholder="Descrição do álbum..."></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Criar Álbum
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>