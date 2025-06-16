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

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Remover Fotos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS (opcional) -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="container py-4">
        <h2 class="mb-4 text-center">Remover Fotos do Álbum</h2>

        <?php if (empty($photos)): ?>
            <p class="text-center">Este álbum não tem fotos.</p>
        <?php else: ?>
            <form method="POST" id="removeForm">
                <div class="row g-3">
                    <?php foreach ($photos as $photo): ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center">
                            <div class="border rounded p-2 h-100">
                                <img src="<?= htmlspecialchars($photo['filepath']) ?>" alt="Foto"
                                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                                <input type="checkbox" name="photo_ids[]" value="<?= $photo['id'] ?>" class="form-check-input mt-2">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleSelectAll()">
                        <i class="bi bi-check2-square"></i> Selecionar Todos
                    </button>
                    <button type="submit" class="btn btn-danger" onclick="return confirmDelete()">
                        <i class="bi bi-trash3"></i> Remover Selecionadas
                    </button>
                    <a href="../album/album.php?id=<?= $albumId ?>" class="btn btn-outline-dark">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </main>

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>