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

$userId = $_SESSION['user_id'];
$albumId = intval($_GET['album_id']);

$conn = db_connect();

// Verificar se o utilizador pertence ao álbum
$stmt = $conn->prepare("SELECT role FROM user_album WHERE album_id = ? AND user_id = ?");
$stmt->bind_param("ii", $albumId, $userId);
$stmt->execute();
$stmt->bind_result($currentUserRole);
if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    echo "Acesso negado ao álbum.";
    exit;
}
$stmt->close();

// Guardar permissões
$isAdmin = $currentUserRole === 'Administrador';


// Obter os participantes
$stmt = $conn->prepare("
    SELECT ua.user_id, u.username, u.email, ua.role
    FROM user_album ua
    JOIN user u ON ua.user_id = u.id
    WHERE ua.album_id = ?
");
$stmt->bind_param("i", $albumId);
$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Gerir Participantes</title>
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
        <h2>Gerir Participantes</h2>
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Permissão</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($participants as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if ($isAdmin): ?>
                            <form method="POST" action="../../controllers/alterar_role.php" style="display: inline-block;">
                                <input type="hidden" name="album_id" value="<?= $albumId ?>">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <select name="new_role" onchange="this.form.submit()">
                                    <option value="Utilizador" <?= $user['role'] === 'Utilizador' ? 'selected' : '' ?>>Utilizador</option>
                                    <option value="Moderador" <?= $user['role'] === 'Moderador' ? 'selected' : '' ?>>Moderador</option>
                                    <option value="Administrador" <?= $user['role'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                            </form>
                        <?php else: ?>
                            <?= htmlspecialchars($user['role']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isAdmin && $user['user_id'] != $userId): ?>
                            <form method="POST" action="../../controllers/remover_participante.php" onsubmit="return confirm('Tem a certeza que quer remover este participante?');">
                                <input type="hidden" name="album_id" value="<?= $albumId ?>">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit">Remover</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="rightbar"></div>
</div>
</body>
</html>
