<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$conn = db_connect();
$userId = $_SESSION['user_id'];

$query = "
    SELECT 
        a.id, a.title, a.description,
        (SELECT p.filepath FROM photo p WHERE p.album_id = a.id ORDER BY p.upload_at ASC LIMIT 1) AS cover
    FROM album a
    JOIN user_album ua ON a.id = ua.album_id
    WHERE ua.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$albums = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
    <title>Meus Álbuns</title>
    <link rel="stylesheet" href="../../assets/styles/homepage.css">
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
        <button style="background-color: #ddd;" onclick="location.href='albuns.php'">🖼️</button>
        <button onclick="location.href='likes.php'">👍</button>
    </div>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Os teus álbuns</h2>
            <button onclick="location.href='criar_album.php'" style="padding: 8px 12px;">➕ Criar Álbum</button>
        </div>

        <div class="albums-grid">
            <?php if (empty($albums)): ?>
                <p>Não tens álbuns associados.</p>
            <?php else: ?>
                <?php foreach ($albums as $album): ?>
                    <div class="album-card">
                        <a href="album.php?id=<?= $album['id'] ?>">
                            <img src="<?= htmlspecialchars($album['cover'] ?? '../../assets/styles/imgs/placeholder.png') ?>" alt="Capa do álbum">
                            <p><?= htmlspecialchars($album['title']) ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="rightbar"></div>
</div>
</body>
</html>
