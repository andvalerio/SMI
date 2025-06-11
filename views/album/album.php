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
$albumId = intval($_GET['id']);
$conn = db_connect();

// Verificar se o utilizador tem acesso ao album e obter informacao
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
    echo "Acesso negado ao álbum.";
    exit;
}
$stmt->close();

// Guardar a role do utilizador neste álbum
if (!isset($_SESSION['album_roles'])) {
    $_SESSION['album_roles'] = [];
}
$_SESSION['album_roles'][$albumId] = $role;

// Obter fotos do album
$photos_stmt = $conn->prepare("SELECT filepath FROM photo WHERE album_id = ? ORDER BY upload_at DESC");
$photos_stmt->bind_param("i", $albumId);
$photos_stmt->execute();
$result = $photos_stmt->get_result();
$photos = $result->fetch_all(MYSQLI_ASSOC);
$photos_stmt->close();
$conn->close();

// Definir permissoes
$can_edit = hasAlbumRole($albumId, 'Administrador');
$can_upload = hasAlbumRole($albumId, 'Administrador') || hasAlbumRole($albumId, 'Moderador');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../../assets/styles/homepage.css">
</head>
<body>
<header>
    <div><strong onclick="location.href='homepage.php'">Photo Gallery</strong></div>
    <input type="text" placeholder="search">
    <div>
        <button title="Definições">⚙️</button>
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
        <button>👍</button>
        <button>👥</button>
    </div>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2><?= htmlspecialchars($title) ?></h2>
            <div>
                <?php if ($can_upload): ?>
                    <a href="../photo/upload_fotos.php?album_id=<?= $albumId ?>"><button>📤 Adicionar Fotos</button></a>
                <?php endif; ?>
                <?php if ($can_edit): ?>
                    <a href="editar_album.php?album_id=<?= $albumId ?>"><button>✏️ Editar Álbum</button></a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($description): ?>
            <p><strong>Descrição:</strong></p>
            <p><?= nl2br(htmlspecialchars($description)) ?></p>
        <?php endif; ?>

        <div class="photos">
            <?php if (empty($photos)): ?>
                <p>Não há fotos neste álbum ainda.</p>
            <?php else: ?>
                <?php foreach ($photos as $photo): ?>
                    <div>
                        <img src="<?= htmlspecialchars($photo['filepath']) ?>" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="rightbar"></div>
</div>
</body>
</html>
