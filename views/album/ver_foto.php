<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isset($_GET['path'])) {
    echo "Foto não encontrada.";
    exit;
}

$filepath = $_GET['path'];
$conn = db_connect();

// Obter info da foto
$stmt = $conn->prepare("SELECT id, album_id FROM photo WHERE filepath = ?");
$stmt->bind_param("s", $filepath);
$stmt->execute();
$stmt->bind_result($photoId, $albumId);
if (!$stmt->fetch()) {
    echo "Foto não encontrada.";
    exit;
}
$stmt->close();

// Verificar se já deu like
$userId = $_SESSION['user_id'] ?? null;
$alreadyLiked = false;

if ($userId) {
    $checkLikeStmt = $conn->prepare("SELECT 1 FROM photo_likes WHERE photo_id = ? AND user_id = ?");
    $checkLikeStmt->bind_param("ii", $photoId, $userId);
    $checkLikeStmt->execute();
    $checkLikeStmt->store_result();
    $alreadyLiked = $checkLikeStmt->num_rows > 0;
    $checkLikeStmt->close();
}

// Verifica se o utilizador pode interagir
$userCanInteract = isLoggedIn() && (
    hasAlbumRole($albumId, 'Utilizador') ||
    hasAlbumRole($albumId, 'Moderador') ||
    hasAlbumRole($albumId, 'Administrador')
);

// Obter likes
$likes_stmt = $conn->prepare("SELECT COUNT(*) FROM photo_likes WHERE photo_id = ?");
$likes_stmt->bind_param("i", $photoId);
$likes_stmt->execute();
$likes_stmt->bind_result($likes);
$likes_stmt->fetch();
$likes_stmt->close();

// Obter quem deu like
$like_users_stmt = $conn->prepare("
    SELECT u.username 
    FROM photo_likes pl 
    JOIN user u ON pl.user_id = u.id 
    WHERE pl.photo_id = ?
");
$like_users_stmt->bind_param("i", $photoId);
$like_users_stmt->execute();
$like_users_result = $like_users_stmt->get_result();
$likedUsers = $like_users_result->fetch_all(MYSQLI_ASSOC);
$like_users_stmt->close();

// Obter número total de downloads
$downloads_stmt = $conn->prepare("SELECT COUNT(*) FROM photo_downloads WHERE photo_id = ?");
$downloads_stmt->bind_param("i", $photoId);
$downloads_stmt->execute();
$downloads_stmt->bind_result($totalDownloads);
$downloads_stmt->fetch();
$downloads_stmt->close();

// Contar número de comentários
$commentCountStmt = $conn->prepare("SELECT COUNT(*) FROM photo_comments WHERE photo_id = ?");
$commentCountStmt->bind_param("i", $photoId);
$commentCountStmt->execute();
$commentCountStmt->bind_result($commentCount);
$commentCountStmt->fetch();
$commentCountStmt->close();


// Obter comentários
$comments_stmt = $conn->prepare("
    SELECT u.username, c.comment, c.created_at 
    FROM photo_comments c 
    JOIN user u ON c.user_id = u.id 
    WHERE c.photo_id = ? 
    ORDER BY c.created_at DESC
");
$comments_stmt->bind_param("i", $photoId);
$comments_stmt->execute();
$comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$comments_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Foto</title>
    <link rel="stylesheet" href="../../assets/styles/homepage.css">
</head>
<body>
    <div style="padding: 20px;">
        <a href="album.php?id=<?= $albumId ?>">← Voltar ao Álbum</a>
        <div style="margin-top: 20px;">
            <img src="<?= htmlspecialchars($filepath) ?>" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
            <p><strong>Likes:</strong> <?= $likes ?></p>
            <?php if (!empty($likedUsers)): ?>
                <p>❤️ <em>Gostado por:</em> <?= implode(', ', array_map(fn($u) => htmlspecialchars($u['username']), $likedUsers)) ?></p>
            <?php endif; ?>
            <p><strong>Downloads:</strong> <?= $totalDownloads ?></p>
            <?php if ($userCanInteract): ?>
                <form action="../../controllers/like_foto.php" method="post" style="margin-bottom: 10px;">
                    <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                    <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                    <button type="submit">
                        <?= $alreadyLiked ? '👎 Remover Like' : '👍 Dar Like' ?>
                    </button>
                </form>
                <form action="../../controllers/comentar_foto.php" method="post">
                    <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                    <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                    <textarea name="comment" rows="3" cols="50" required placeholder="Escreve um comentário..."></textarea><br>
                    <button type="submit">💬 Comentar</button>
                </form>
                <form action="../../controllers/download_foto.php" method="post" style="margin-top: 10px;">
                    <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                    <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                    <button type="submit">⬇️ Download da Imagem</button>
                </form>
            <?php else: ?>
                <p><em>Apenas utilizadores registados podem interagir com esta foto.</em></p>
            <?php endif; ?>

            <h3>Comentários (<?= $commentCount ?>):</h3>
            <?php if (empty($comments)): ?>
                <p>Sem comentários ainda.</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div style="border-bottom: 1px solid #ccc; margin-bottom: 10px;">
                        <strong><?= htmlspecialchars($c['username']) ?></strong> disse:<br>
                        <?= nl2br(htmlspecialchars($c['comment'])) ?><br>
                        <small><?= $c['created_at'] ?></small>
                        <?php $canDeleteComment = $userCanInteract && (
                            (isset($_SESSION['username']) && $_SESSION['username'] === $c['username']) ||
                            hasAlbumRole($albumId, 'Moderador') ||
                            hasAlbumRole($albumId, 'Administrador')
                        );
                        if ($canDeleteComment):
                        ?>
                        <form action="../../controllers/apagar_comentario.php" method="post" style="display:inline;">
                            <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                            <input type="hidden" name="comment_user" value="<?= htmlspecialchars($c['username']) ?>">
                            <input type="hidden" name="created_at" value="<?= $c['created_at'] ?>">
                            <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                            <button type="submit" style="color: red; border: none; background: none; cursor: pointer;">🗑️ Apagar</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
