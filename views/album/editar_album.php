<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$albumId = intval($_GET['album_id']);
$conn = db_connect();

// Verifica se o utilizador tem permissão de Administrador neste álbum
$stmt = $conn->prepare("
    SELECT a.title, a.description, ua.role
    FROM album a
    JOIN user_album ua ON a.id = ua.album_id
    WHERE a.id = ? AND ua.user_id = ?
");
$stmt->bind_param("ii", $albumId, $userId);
$stmt->execute();
$stmt->bind_result($title, $description, $role);

if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    echo "Álbum não encontrado ou acesso negado.";
    exit();
}
$stmt->close();

if ($role !== 'Administrador') {
    $conn->close();
    echo "Apenas administradores podem editar este álbum.";
    exit();
}

if (!isset($_SESSION['album_roles'])) {
    $_SESSION['album_roles'] = [];
}
$_SESSION['album_roles'][$albumId] = $role;

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_album'])) {
        $stmt = $conn->prepare("SELECT filepath FROM photo WHERE album_id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (file_exists($row['filepath'])) {
                unlink($row['filepath']);
            }
        }
        $stmt->close();

        $albumDir = $CONTENT_FOLDER . $albumId;
        function rrmdir($dir)
        {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        $path = $dir . DIRECTORY_SEPARATOR . $object;
                        is_dir($path) ? rrmdir($path) : unlink($path);
                    }
                }
                rmdir($dir);
            }
        }
        rrmdir($albumDir);

        $stmt = $conn->prepare("DELETE FROM photo WHERE album_id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM user_album WHERE album_id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM album WHERE id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $stmt->close();

        $conn->close();
        header("Location: albuns.php");
        exit();
    }

    if (isset($_POST['update_album'])) {
        $new_title = trim($_POST['title']);
        $new_desc = trim($_POST['description']);

        $stmt = $conn->prepare("UPDATE album SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_title, $new_desc, $albumId);

        if ($stmt->execute()) {
            $title = $new_title;
            $description = $new_desc;
            $msg = "Álbum atualizado com sucesso.";
        } else {
            $msg = "Erro ao atualizar o álbum.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Editar Álbum <?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <h2 class="text-center mb-4">Editar Álbum</h2>

                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-info"> <?= htmlspecialchars($msg) ?> </div>
                    <?php endif; ?>

                    <form method="post" class="mb-4">
                        <input type="hidden" name="update_album" value="1">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success">Guardar Alterações</button>
                        </div>
                    </form>

                    <form method="post" id="deleteForm">
                        <div class="text-center">
                            <input type="hidden" name="delete_album" value="1">
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Apagar Álbum</button>
                        </div>
                    </form>

                    <a href="album.php?id=<?= $albumId ?>">Voltar</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function confirmDelete() {
            if (confirm('Tem a certeza que deseja apagar este álbum? Esta ação não pode ser desfeita.')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>