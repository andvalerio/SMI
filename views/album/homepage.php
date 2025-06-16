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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <header class="d-flex justify-content-between align-items-center px-4">
        <strong onclick="location.href='homepage.php'" class="fs-4" style="cursor:pointer">Photo Gallery</strong>

        <div class="d-flex align-items-center gap-2">
            <!-- Botões pa ver albuns -->
            <button class="btn btn-light btn-sm" onclick="location.href='albuns.php'" title="Álbuns">
                <i class="bi bi-images"></i>
            </button>
            <!-- Botões pa ver likes -->
            <button class="btn btn-light btn-sm" onclick="location.href='likes.php'" title="Likes">
                <i class="bi bi-heart-fill"></i>
            </button>

            <!-- Botão de notificações -->
            <button class="btn btn-light btn-sm position-relative" onclick="location.href='notificacoes.php'" title="Notificações">
                <i class="bi bi-bell-fill"></i>
                <?php if ($notificacao_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $notificacao_count ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown de utilizador -->
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Conta">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../auth/account.php">Alterar dados da conta</a></li>
                    <li><a class="dropdown-item" href="../logout.php">Terminar sessão</a></li>
                </ul>
            </div>
        </div>
    </header>


    <main class="flex-grow-1 p-4">
        <h2 class="text-primary mb-4">Fotos Recentes</h2>
        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-card">
                    <a href="album.php?id=<?= urlencode($photo['album_id'] ?? '') ?>">
                        <img src="<?= htmlspecialchars($photo['filepath']); ?>" alt="<?= htmlspecialchars($photo['filename']); ?>">
                    </a>
                    <div class="mt-2 text-muted small">
                        Álbum: <?= htmlspecialchars($photo['title']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>