<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() && !isset($_SESSION['guest_album'])) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$albumId = intval($_GET['id']);
$conn = db_connect();

if (isset($_SESSION['guest_album']) && $_SESSION['guest_album'] == $albumId && !$userId) {
    // Convidado com código
    $title = $description = '';
    $stmt = $conn->prepare("SELECT title, description FROM album WHERE id = ?");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $stmt->bind_result($title, $description);
    if (!$stmt->fetch()) {
        echo "Álbum não encontrado.";
        exit;
    }
    $stmt->close();
    $_SESSION['album_roles'][$albumId] = 'Convidado';
} else {
    // Verificar se o utilizador tem acesso ao álbum
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
        echo "Acesso negado ao álbum.";
        exit;
    }
    $stmt->close();

    $_SESSION['album_roles'][$albumId] = $role;
}


$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$_SESSION['album_title'] = $title; // Guardar o título do álbum na sessão

// Obter fotos do album
$photos_stmt = $conn->prepare("
    SELECT 
        p.id, p.filepath, p.filename,
        u.username,
        (SELECT COUNT(*) FROM photo_likes WHERE photo_id = p.id) AS likes,
        (SELECT COUNT(*) FROM photo_comments WHERE photo_id = p.id) AS comments,
        (SELECT COUNT(*) FROM photo_downloads WHERE photo_id = p.id) AS downloads
    FROM photo p
    LEFT JOIN user u ON p.upload_by = u.id
    WHERE p.album_id = ?
    ORDER BY p.upload_at DESC
");
$photos_stmt->bind_param("i", $albumId);
$photos_stmt->execute();
$result = $photos_stmt->get_result();
$photos = $result->fetch_all(MYSQLI_ASSOC);
$photos_stmt->close();

// Obter código de acesso do álbum
$code_stmt = $conn->prepare("SELECT access_code FROM album WHERE id = ?");
$code_stmt->bind_param("i", $albumId);
$code_stmt->execute();
$code_stmt->bind_result($accessCode);
$code_stmt->fetch();
$code_stmt->close();
$conn->close();

// Definir permissoes
$can_edit = hasAlbumRole($albumId, 'Administrador');
$can_add_user = hasAlbumRole($albumId, 'Administrador') || hasAlbumRole($albumId, 'Moderador');
$can_upload = hasAlbumRole($albumId, 'Administrador') || hasAlbumRole($albumId, 'Moderador') || hasAlbumRole($albumId, 'Utilizador');

// Verificar número de participantes
$conn = db_connect();
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM user_album WHERE album_id = ?");
$count_stmt->bind_param("i", $albumId);
$count_stmt->execute();
$count_stmt->bind_result($numParticipants);
$count_stmt->fetch();
$count_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php if ($can_upload): ?>
        <?php include_once '../../includes/header.php'; ?>
    <?php else: ?>
        <header class="d-flex justify-content-center align-items-center px-4">
            <strong onclick="location.href='../auth/login.php'" class="fs-4" style="cursor:pointer">Photo Gallery</strong>
        </header>
    <?php endif; ?>

    <main class="flex-grow-1 p-4">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <h2 class="text-secondary"><?= htmlspecialchars($title) ?></h2>
            <div class="d-flex flex-wrap align-items-center gap-2">

                <?php if ($can_upload): ?>
                    <span class="me-2">Código do Álbum:</span>
                    <input type="text" id="accessCode" value="<?= htmlspecialchars($accessCode) ?>" class="form-control form-control-sm" style="width: 130px;" readonly>
                    <button class="btn btn-outline-secondary btn-sm" onclick="copyAccessCode()"><i class="bi bi-clipboard"></i></button>
                    <a href="gerir_participantes.php?album_id=<?= $albumId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-people"></i> Membros</a>
                    <a href="uploads.php?album_id=<?= $albumId ?>" class="btn btn-sm btn-success"><i class="bi bi-upload"></i> Adicionar Fotos</a>
                <?php endif; ?>

                <?php if ($can_add_user): ?>
                    <button class="btn btn-sm btn-warning" onclick="openAddUserModal()"><i class="bi bi-person-plus"></i> Adicionar Utilizador</button>
                <?php endif; ?>

                <?php if ($can_edit): ?>
                    <a href="editar_album.php?album_id=<?= $albumId ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Editar Álbum</a>
                    <a href="remover_fotos.php?album_id=<?= $albumId ?>" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Remover Fotos</a>
                <?php endif; ?>

                <?php if ($numParticipants > 1 && $can_upload): ?>
                    <form method="POST" action="../../controllers/sair_album.php" onsubmit="return confirm('Tem a certeza que quer sair do álbum?');" class="d-inline">
                        <input type="hidden" name="album_id" value="<?= $albumId ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Sair</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($description): ?>
            <div class="mb-4">
                <p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($description)) ?> </p>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-center">
            <?php if (empty($photos)): ?>
                <p>Não há fotos neste álbum ainda.</p>
            <?php else: ?>
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-card">
                        <a href="ver_foto.php?path=<?= urlencode($photo['filepath']) ?>">
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
                            <i class="bi bi-person"></i> <?= htmlspecialchars($photo['username'] ?? 'Desconhecido') ?>
                        </div>
                        <div class="photo-stats small text-muted mt-1">
                            <i class="bi bi-heart-fill"></i> <?= $photo['likes'] ?>
                            <i class="bi bi-chat-dots"></i> <?= $photo['comments'] ?>
                            <i class="bi bi-download"></i> <?= $photo['downloads'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="addUser">
        <div class="bg-white rounded shadow p-4" style="width: 400px; margin: 100px auto;">
            <h5 class="mb-3">Adicionar Utilizador ao Álbum</h5>
            <form method="POST" action="../../controllers/adicionar_utilizador.php">
                <input type="hidden" name="album_id" value="<?= $albumId ?>">
                <div class="mb-3">
                    <label for="user_identifier" class="form-label">Username ou Email</label>
                    <input type="text" id="user_identifier" name="user_identifier" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Permissão</label>
                    <select id="role" name="role" class="form-select">
                        <option value="Utilizador">Utilizador</option>
                        <option value="Moderador">Moderador</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeAddUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            document.getElementById("addUser").style.display = "block";
        }

        function closeAddUserModal() {
            document.getElementById("addUser").style.display = "none";
        }

        function copyAccessCode() {
            const input = document.getElementById("accessCode");
            input.select();
            document.execCommand("copy");
            alert("Código copiado!");
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>