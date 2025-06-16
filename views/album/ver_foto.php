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
    <title>Visualizar Foto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="flex-grow-1 p-4">
        <a href="album.php?id=<?= $albumId ?>" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Voltar ao Álbum
        </a>

        <div class="text-center">
            <img src="<?= htmlspecialchars($filepath) ?>"
                class="img-fluid border rounded shadow-lg"
                style="max-height: 50vh; object-fit: contain;">

            <?php if ($userCanInteract): ?>
                <div class="mt-2 d-flex justify-content-center gap-2 mb-3">
                    <form action="../../controllers/like_foto.php" method="post" class="mb-2">
                        <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                        <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi <?= $alreadyLiked ? 'bi-hand-thumbs-down' : 'bi-hand-thumbs-up' ?>"></i>
                            <?= $alreadyLiked ? 'Remover Like' : 'Dar Like' ?>
                        </button>
                    </form>

                    <form action="../../controllers/download_foto.php" method="post">
                        <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                        <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                        <button type="submit" class="btn btn-outline-dark">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <p class="text-muted"><em>Apenas utilizadores registados podem interagir com esta foto.</em></p>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <div class="d-flex justify-content-center align-items-center" style="min-height: 60px;">
                <p>
                    <?= $likes ?> <i class="bi bi-heart-fill text-danger"></i>
                    <?php if (!empty($likedUsers)): ?>
                        <em>Gostado por: <?= implode(', ', array_map(fn($u) => htmlspecialchars($u['username']), $likedUsers)) ?></em>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex justify-content-center align-items-center">
                <p><?= $totalDownloads ?> <strong>downloads</strong> efetuados.</p>
            </div>

            <h3 class="mt-2">Comentários (<?= $commentCount ?>):</h3>
            <?php if ($userCanInteract): ?>
                <form action="../../controllers/comentar_foto.php" method="post" class="mb-3">
                    <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                    <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">

                    <div class="d-flex align-items-start gap-2">
                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-chat-left-text"></i>
                        </button>
                        <textarea name="comment" rows="1" class="form-control" required placeholder="Escreve um comentário..." style="resize: none;"></textarea>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (empty($comments)): ?>
                <p class="text-muted">Sem comentários ainda.</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div class="border-bottom pb-2 mb-3">
                        <strong><?= htmlspecialchars($c['username']) ?></strong> disse:<br>
                        <p class="mb-1"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                        <small class="text-muted"><?= $c['created_at'] ?></small>
                        <?php $canDeleteComment = $userCanInteract && (
                            (isset($_SESSION['username']) && $_SESSION['username'] === $c['username']) ||
                            hasAlbumRole($albumId, 'Moderador') ||
                            hasAlbumRole($albumId, 'Administrador')
                        );
                        if ($canDeleteComment): ?>
                            <form action="../../controllers/apagar_comentario.php" method="post" class="d-inline ms-2">
                                <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                                <input type="hidden" name="comment_user" value="<?= htmlspecialchars($c['username']) ?>">
                                <input type="hidden" name="created_at" value="<?= $c['created_at'] ?>">
                                <input type="hidden" name="path" value="<?= htmlspecialchars($filepath) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Apagar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>