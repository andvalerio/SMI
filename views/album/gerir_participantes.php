<?php
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

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Gerir Participantes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="container py-5">
        <h2 class="mb-4 text-center">Gerir Participantes</h2>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Permissão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <form method="POST" action="../../controllers/alterar_role.php">
                                        <input type="hidden" name="album_id" value="<?= $albumId ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <select name="new_role" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="Utilizador" <?= $user['role'] === 'Utilizador' ? 'selected' : '' ?>>Utilizador</option>
                                            <option value="Moderador" <?= $user['role'] === 'Moderador' ? 'selected' : '' ?>>Moderador</option>
                                            <option value="Administrador" <?= $user['role'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <?= htmlspecialchars($user['role']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isAdmin && $user['user_id'] != $userId): ?>
                                    <form method="POST" action="../../controllers/remover_participante.php"
                                        onsubmit="return confirm('Tem a certeza que quer remover este participante?');" class="d-inline">
                                        <input type="hidden" name="album_id" value="<?= $albumId ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Remover
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="text-end">
                <a class="btn btn-link mt-2" href="album.php?id=<?= $albumId ?>">Voltar atrás</a>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>