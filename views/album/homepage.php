<?php
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
    <?php include_once '../../includes/header.php'; ?>

    <main class="flex-grow-1 p-4">
        <h2 class="text-secondary mb-4">Fotos Recentes</h2>
        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">

            <?php
            if (count($photos) > 0):
                foreach ($photos as $photo): ?>
                    <div class="photo-card">
                        <a href="album.php?id=<?= urlencode($photo['album_id'] ?? '') ?>">
                            <?php
                            $ext = strtolower(pathinfo($photo['filename'], PATHINFO_EXTENSION));
                            $is_video = in_array($ext, ['mp4', 'mov']);
                            if ($is_video): ?>
                                <video autoplay muted loop playsinline
                                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                                    <source src="<?= htmlspecialchars($photo['filepath']) ?>" type="video/mp4">
                                </video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($photo['filepath']); ?>" alt="<?= htmlspecialchars($photo['filename']); ?>">
                            <?php endif; ?>
                        </a>
                        <div class="mt-2 text-muted small">
                            Álbum: <?= htmlspecialchars($photo['title']) ?>
                        </div>
                    </div>
                <?php
                endforeach;
            else: ?>
                <div class="d-flex justify-content-center align-items-center flex-column text-center">
                    <p>Ainda não tem uma foto a apresentar.</p>
                    <a class="btn btn-secondary mt-2" href="albuns.php">Crie agora um álbum</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>