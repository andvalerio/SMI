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

$albumId = intval($_GET['album_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!hasAlbumRole($albumId, 'Administrador')) {
    echo "Acesso negado. Apenas administradores podem remover fotos.";
    exit();
}

$conn = db_connect();

// Processar remoção
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_ids'])) {
    $photoIds = $_POST['photo_ids'];

    $in = str_repeat('?,', count($photoIds) - 1) . '?';
    $types = str_repeat('i', count($photoIds));
    $stmt = $conn->prepare("SELECT filepath FROM photo WHERE id IN ($in) AND album_id = ?");
    $params = array_merge($photoIds, [$albumId]);
    $stmt->bind_param($types . 'i', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $filepaths = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM photo WHERE id IN ($in) AND album_id = ?");
    $stmt->bind_param($types . 'i', ...$params);
    $stmt->execute();
    $stmt->close();

    foreach ($filepaths as $fp) {
        $path = $fp['filepath'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    $_SESSION['success_message'] = "Fotos removidas com sucesso.";
    header("Location: album.php?id=$albumId");
    exit();
}

$stmt = $conn->prepare("SELECT id, filepath FROM photo WHERE album_id = ? ORDER BY upload_at DESC");
$stmt->bind_param("i", $albumId);
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
    <title>Remover Fotos</title>
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>
<body>
    <div class="main">
        <div class="center-content">
            <h2>Remover Fotos do Álbum</h2>

            <?php if (empty($photos)): ?>
                <p>Este álbum não tem fotos.</p>
            <?php else: ?>
                <form method="POST" id="removeForm">
                    <div class="remove-photo-grid">
                        <?php foreach ($photos as $photo): ?>
                            <div class="remove-photo-item">
                                <input type="checkbox" name="photo_ids[]" value="<?= $photo['id'] ?>">
                                <img src="<?= htmlspecialchars($photo['filepath']) ?>" alt="Foto">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="button-group">
                        <button type="button" class="secondary" onclick="toggleSelectAll()">Selecionar Todos</button>
                        <button type="submit" class="danger" onclick="return confirmDelete()">🗑️ Remover Selecionadas</button>
                        <a href="../album/album.php?id=<?= $albumId ?>" class="secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let allSelected = false;
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('input[name="photo_ids[]"]');
            allSelected = !allSelected;
            checkboxes.forEach(cb => cb.checked = allSelected);
        }

        function confirmDelete() {
            const selected = document.querySelectorAll('input[name="photo_ids[]"]:checked');
            if (selected.length === 0) {
                alert("Por favor, selecione pelo menos uma foto para remover.");
                return false;
            }
            return confirm("Tem a certeza que deseja remover as fotos selecionadas? Esta ação não pode ser desfeita.");
        }
    </script>
</body>
</html>
