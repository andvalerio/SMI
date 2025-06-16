<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = db_connect();
$userId = $_SESSION['user_id'];
$msg = "";

// Apagar conta
if (isset($_POST['delete_account'])) {
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Atualização dos dados
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_changes'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $password = $_POST['password'] ?? null;

    $stmt = $conn->prepare("SELECT id FROM user WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $msg = "Erro: Nome de utilizador ou email já está a ser usado.";
    } else {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, full_name = ?, password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $full_name, $password_hash, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, full_name = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $full_name, $userId);
        }

        if ($stmt->execute()) {
            $msg = "Dados atualizados com sucesso!";
            $_SESSION['username'] = $username;
        } else {
            $msg = "Erro ao atualizar dados.";
        }
    }
    $stmt->close();
}

// dados atuais
$stmt = $conn->prepare("SELECT username, email, full_name FROM user WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($currentUsername, $currentEmail, $currentFullName);
$stmt->fetch();
$stmt->close();

// álbuns criados pelo utilizador
$stmt = $conn->prepare("SELECT id, title, created_at FROM album WHERE owner_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$albums = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Conta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">

</head>

<body>
    <header class="d-flex justify-content-center align-items-center px-4">
        <strong onclick="location.href='../album/homepage.php'" class="fs-4" style="cursor:pointer">Photo Gallery</strong>
    </header>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                <?php if (!empty($msg)): ?>
                    <div class="alert alert-info text-center">
                        <strong><?= $msg ?></strong>
                    </div>
                <?php endif; ?>

                <!-- Formulário de edição -->
                <div class="bg-white p-4 rounded shadow-sm mb-4">
                    <h2 class="text-center mb-4">Alterar Dados da Conta</h2>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nome de utilizador</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($currentUsername) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($currentEmail) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nome completo</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($currentFullName) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Password (opcional)</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" name="save_changes" class="btn btn-primary">Guardar alterações</button>

                            <!-- Formulário de apagar conta -->
                            <form action="" method="POST" onsubmit="return confirm('Tens a certeza que queres apagar a tua conta? Esta ação é irreversível.');">
                                <button type="submit" name="delete_account" class="btn btn-danger">Apagar Conta</button>
                            </form>
                        </div>
                    </form>
                </div>

                <!-- Secção de álbuns -->
                <div>
                    <h2 class="mb-3">Álbuns Criados</h2>
                    <?php if (empty($albums)): ?>
                        <p class="text-muted">Não criaste nenhum álbum.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($albums as $album): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($album['title']) ?>
                                    <span class="badge bg-secondary">
                                        <?= date('d/m/Y', strtotime($album['created_at'])) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>


</html>