<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // Visitante com código
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
        p.id, p.filepath,
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
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../../assets/styles/homepage.css">
    <style>
        .photo-card {
            width: 220px;
            margin-left: 60px;
            margin-right: 60px;
            margin-bottom: 190px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-card img {
            width: 220px;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .photo-stats {
            width: 220px;
            font-size: 14px;
            color: #555;
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            box-sizing: border-box;
        }

        .photos {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
    </style>


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
        <?php if (!empty($successMessage)): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2><?= htmlspecialchars($title) ?></h2>
            <div style="display: flex; gap: 8px;">
                <?php if ($can_upload): ?>
                    <label for="accessCode" style="font-size: 14px;">Código do Álbum:</label>
                    <input type="text" id="accessCode" value="<?= htmlspecialchars($accessCode) ?>" readonly style="width: 130px;">
                    <button onclick="copyAccessCode()">📋 Copiar</button>

                    <a href="upload_fotos.php?album_id=<?= $albumId ?>"><button>📤 Adicionar Fotos</button></a>
                    <a href="gerir_participantes.php?album_id=<?= $albumId ?>"><button>👥 Membros</button></a>
                <?php endif; ?>
                <?php if ($can_add_user): ?>
                    <button onclick="openAddUserModal()">➕ Adicionar Utilizador</button>
                <?php endif; ?>
                <?php if ($can_edit): ?>
                    <a href="editar_album.php?album_id=<?= $albumId ?>"><button>✏️ Editar Álbum</button></a>
                    <a href="remover_fotos.php?album_id=<?= $albumId ?>"><button>🗑️ Remover Fotos</button></a>
                <?php endif; ?>
                <?php if ($numParticipants > 1 && $can_upload): ?>
                    <form method="POST" action="../../controllers/sair_album.php" onsubmit="return confirm('Tem a certeza que quer sair do álbum?');" style="display:inline;">
                        <input type="hidden" name="album_id" value="<?= $albumId ?>">
                        <button type="submit">🚪 Sair do Álbum</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($description): ?>
            <p><strong>Descrição:</strong></p>
            <p><?= nl2br(htmlspecialchars($description)) ?></p>
        <?php endif; ?>

        <div class="photos">
            <?php if (empty($photos)): ?>
                <p>Não há fotos neste álbum ainda.</p>
            <?php else: ?>
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-card">
                        <a href="ver_foto.php?path=<?= urlencode($photo['filepath']) ?>">
                            <img src="<?= htmlspecialchars($photo['filepath']) ?>">
                        </a>
                        <div style="margin-top: 4px; font-size: 13px; color: #333;">
                            👤 <?= htmlspecialchars($photo['username'] ?? 'Desconhecido') ?>
                        </div>
                        <div class="photo-stats">
                            ❤️ <?= $photo['likes'] ?>
                            💬 <?= $photo['comments'] ?>
                            ⬇️ <?= $photo['downloads'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    <div id="addUser" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
        <div style="background:white; width:400px; margin:100px auto; padding:20px; border-radius:8px; position:relative;">
            <h3>Adicionar Utilizador ao Álbum</h3>
            <form method="POST" action="../../controllers/adicionar_utilizador.php">
                <input type="hidden" name="album_id" value="<?= $albumId ?>">
                <label for="user_identifier">Username ou Email:</label>
                <input type="text" id="user_identifier" name="user_identifier" required style="width:100%; margin-bottom:10px;">
                <label for="role">Permissão:</label>
                <select id="role" name="role" style="width:100%; margin-bottom:15px;">
                    <option value="Utilizador">Utilizador</option>
                    <option value="Moderador">Moderador</option>
                </select>
                <div style="display: flex; justify-content: space-between;">
                    <button type="submit">Adicionar</button>
                    <button type="button" onclick="closeAddUserModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>


    <div class="rightbar"></div>
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

</body>
</html>
