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

    // Obter as 24 fotos mais recentes dos álbuns que o utilizador pode ver
    $query = "
    SELECT p.filepath, p.filename, p.upload_at, a.title, a.id AS album_id
    FROM photo p
    JOIN album a ON p.album_id = a.id
    JOIN user_album ua ON ua.album_id = a.id
    WHERE ua.user_id = ?
    ORDER BY p.upload_at DESC
    LIMIT 24
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $photos = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Photo Gallery</title>
    <link rel="stylesheet" href="../../assets/styles/main.css">
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

    <div class="content">
        <h2>Fotos Recentes</h2>
        <div class="photos">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-card">
                    <a href="album.php?id=<?= urlencode($photo['album_id'] ?? '') ?>">
                        <img src="<?= htmlspecialchars($photo['filepath']); ?>" alt="<?= htmlspecialchars($photo['filename']); ?>">
                    </a>
                    <div style="margin-top: 4px; font-size: 13px; color: #333;">
                        Álbum: <?= htmlspecialchars($photo['title']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rightbar"></div>
</div>
</body>
</html>