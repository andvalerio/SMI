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
    $album_name = preg_replace('/[^a-zA-Z0-9 _-]/', '', $album_name);

    if (empty($album_name)) {
        $erros .= "Erro: Nome inválido para o título do álbum. <br>";
    } else {
        $description = $_POST["description"];
        $access_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

        $stmt_album = $conn->prepare("INSERT INTO album (title, description, access_code, owner_id) VALUES (?, ?, ?, ?)");
        $stmt_album->bind_param("sssi", $album_name, $description, $access_code, $userId);

        if ($stmt_album->execute()) {
            $album_id = $stmt_album->insert_id;

            // Atribuir o papel de administrador ao criador do álbum
            $role = "Administrador";
            $stmt_user_album = $conn->prepare("INSERT INTO user_album (album_id, user_id, role) VALUES (?, ?, ?)");
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
    <?php include_once '../../includes/header.php'; ?>

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