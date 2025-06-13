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

// Guarda a role na sessão para consistência com outros ficheiros
if (!isset($_SESSION['album_roles'])) {
    $_SESSION['album_roles'] = [];
}
$_SESSION['album_roles'][$albumId] = $role;

$msg = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_album'])) {
        // Primeiro apagar as fotos da base e ficheiros (se necessário)
        $stmt = $conn->prepare("SELECT filepath FROM photo WHERE album_id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (file_exists($row['filepath'])) {
                unlink($row['filepath']); // Apaga o ficheiro físico
            }
        }
        $stmt->close();

        // Apagar diretoria do álbum (recursivamente)
        $albumDir = $CONTENT_FOLDER . $albumId;
        function rrmdir($dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        $path = $dir . DIRECTORY_SEPARATOR . $object;
                        if (is_dir($path)) {
                            rrmdir($path);
                        } else {
                            unlink($path);
                        }
                    }
                }
                rmdir($dir);
            }
        }
        rrmdir($albumDir);

        // Apagar fotos da DB
        $stmt = $conn->prepare("DELETE FROM photo WHERE album_id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $stmt->close();

        // Apagar associações user_album
        $stmt = $conn->prepare("DELETE FROM user_album WHERE album_id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $stmt->close();

        // Apagar álbum
        $stmt = $conn->prepare("DELETE FROM album WHERE id = ?");
        $stmt->bind_param("i", $albumId);
        $stmt->execute();
        $stmt->close();

        $conn->close();
        header("Location: albuns.php");
        exit();
    }

    // Atualizar álbum
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
    <title>Editar Álbum <?=htmlspecialchars($title)?></title>
    <link rel="stylesheet" href="../../assets/styles/account.css">
    <script>
        function confirmDelete() {
            if (confirm('Tem a certeza que deseja apagar este álbum? Esta ação não pode ser desfeita.')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</head>
<body>
<header>
    <div><strong onclick="location.href='homepage.php'">Photo Gallery</strong></div>
    <div>
        <button title="Notificações" onclick="location.href='notificacoes.php'">
            🔔<?= $notificacao_count > 0 ? "($notificacao_count)" : "" ?>
        </button>
        <div class="user-menu">
            <button title="Conta">👤</button>
            <div class="user-dropdown">
                <a href="../auth/account.php">Alterar dados da conta</a>
                <a href="../logout.php">Terminar sessão</a>
            </div>
        </div>
    </div>
</header>
<div class="main">
    <div class="sidebar">
        <button onclick="location.href='albuns.php'">🖼️</button>
        <button onclick="location.href='likes.php'">👍</button>
    </div>
    <div class="center-content">
        <div class="form-container">
            <h2>Editar Álbum</h2>
            <?php if (!empty($msg)) echo "<p class='message'>" . htmlspecialchars($msg) . "</p>"; ?>
            <form method="post">
                <input type="hidden" name="update_album" value="1">
                <label for="title">Título</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>

                <label for="description">Descrição</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>

                <input type="submit" value="Guardar Alterações">
            </form>

            <hr>

            <form method="post" id="deleteForm">
                <input type="hidden" name="delete_album" value="1">
                <button type="button" onclick="confirmDelete()" style="background-color:#c0392b; color:white; padding:10px; border:none; cursor:pointer;">
                    Apagar Álbum
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
