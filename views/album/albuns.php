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
    <title>Os Meus Álbuns</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-primary mb-4">Os teus álbuns</h2>
            <a href="criar_album.php" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Criar Álbum
            </a>
        </div>

        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">
            <?php if (empty($albums)): ?>
                <p class="text-muted">Não tens álbuns associados.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($albums as $album): ?>
                        <div class="album-card">
                            <a href="album.php?id=<?= $album['id'] ?>">
                                <img src="<?= htmlspecialchars($album['cover'] ?? '../../assets/styles/imgs/placeholder.png') ?>" alt="Capa do álbum">
                                <p class="mt-2 mb-0"><?= htmlspecialchars($album['title']) ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>