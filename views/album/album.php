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

$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
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
$photos_stmt = $conn->prepare("
    SELECT 
        p.id, p.filepath,
        u.username,
        (SELECT COUNT(*) FROM photo_likes WHERE photo_id = p.id) AS likes,
        (SELECT COUNT(*) FROM photo_comments WHERE photo_id = p.id) AS comments,
        (SELECT COUNT(*) FROM photo_downloads WHERE photo_id = p.id) AS downloads
    FROM photo p
    LEFT JOIN user u ON p.upload_by = u.id
    WHERE p.album_id = ?
    ORDER BY p.upload_at DESC
");
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
    <style>
        .photo-card {
            width: 220px;
            margin-left: 60px;
            margin-right: 60px;
            margin-bottom: 190px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-card img {
            width: 220px;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .photo-stats {
            width: 220px;
            font-size: 14px;
            color: #555;
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            box-sizing: border-box;
        }

        .photos {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
    </style>


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
        <button onclick="location.href='likes.php'">👍</button>
        <button>👥</button>
    </div>

    <div class="content">
        <?php if (!empty($successMessage)): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2><?= htmlspecialchars($title) ?></h2>
            <div style="display: flex; gap: 8px;">
                <?php if ($can_upload): ?>
                    <a href="upload_fotos.php?album_id=<?= $albumId ?>"><button>📤 Adicionar Fotos</button></a>
                <?php endif; ?>
                <?php if ($can_edit): ?>
                    <a href="editar_album.php?album_id=<?= $albumId ?>"><button>✏️ Editar Álbum</button></a>
                    <a href="remover_fotos.php?album_id=<?= $albumId ?>"><button>🗑️ Remover Fotos</button></a>
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
                    <div class="photo-card">
                        <a href="ver_foto.php?path=<?= urlencode($photo['filepath']) ?>">
                            <img src="<?= htmlspecialchars($photo['filepath']) ?>">
                        </a>
                        <div style="margin-top: 4px; font-size: 13px; color: #333;">
                            👤 <?= htmlspecialchars($photo['username'] ?? 'Desconhecido') ?>
                        </div>
                        <div class="photo-stats">
                            ❤️ <?= $photo['likes'] ?>
                            💬 <?= $photo['comments'] ?>
                            ⬇️ <?= $photo['downloads'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <div class="rightbar"></div>
</div>
</body>
</html>
