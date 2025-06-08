<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$albumId = intval($_GET['id']);
$conn = db_connect();

// Verifica se o utilizador tem acesso e qual a sua role
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
    echo "Acesso negado.";
    exit;
}
$stmt->close();

// Buscar fotos do álbum
$photos_stmt = $conn->prepare("SELECT filepath FROM photo WHERE album_id = ? ORDER BY upload_at DESC");
$photos_stmt->bind_param("i", $albumId);
$photos_stmt->execute();
$result = $photos_stmt->get_result();
$photos = $result->fetch_all(MYSQLI_ASSOC);
$photos_stmt->close();
$conn->close();

// Definir permissões
$can_edit = $role === 'Administrador';
$can_upload = in_array($role, ['Administrador', 'Moderador']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="styles/homepage.css">
</head>
<body>
<header>
    <div><strong onclick="location.href='index.php'">Photo Gallery</strong></div>
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
                    <a href="upload_fotos.php?album_id=<?= $albumId ?>"><button>📤 Adicionar Fotos</button></a>
                <?php endif; ?>
                <?php if ($can_edit): ?>
                    <a href="editar_album.php?album_id=<?= $albumId ?>"><button>✏️ Editar Álbum</button></a>
                <?php endif; ?>
            </div>
        </div>
        <p><?= nl2br(htmlspecialchars($description)) ?></p>

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
