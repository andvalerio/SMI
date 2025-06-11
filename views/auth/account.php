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
    <link rel="stylesheet" href="../../assets/styles/account.css">
</head>
<body>
    <header>
        <div><strong onclick="location.href='../album/homepage.php'">Photo Gallery</strong></div>
        <input type="text" placeholder="search">
        <div>
            <button title="Definições">⚙️</button>
            <div class="user-menu">
                <button title="Conta">👤</button>
                <div class="user-dropdown">
                    <a href="account.php">Alterar dados da conta</a>
                    <a href="../logout.php">Terminar sessão</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main">
        <div class="sidebar">
            <button onclick="location.href='../album/albuns.php'">🖼️</button>
            <button>👍</button>
            <button>👥</button>
        </div>
        <div class="center-content">
            <div class="form-container">
                <h2>Alterar Dados da Conta</h2>
                <?php if (!empty($msg)) echo "<div class='message'><strong>$msg</strong></div>"; ?>
                <form action="" method="POST">
                    <label for="username">Nome de utilizador</label>
                    <input type="text" name="username" id="username" value="<?= htmlspecialchars($currentUsername) ?>" required>

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($currentEmail) ?>" required>

                    <label for="full_name">Nome completo</label>
                    <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($currentFullName) ?>">

                    <label for="password">Nova Password (opcional)</label>
                    <input type="password" name="password" id="password">

                    <input type="submit" name="save_changes" value="Guardar alterações">
                </form>

                <form action="" method="POST" onsubmit="return confirm('Tens a certeza que queres apagar a tua conta? Esta ação é irreversível.');">
                    <input type="submit" name="delete_account" value="❌ Apagar Conta" class="danger-button">
                </form>
            </div>

            <div class="album-history">
                <h2>Álbuns Criados</h2>
                <?php if (empty($albums)): ?>
                    <p>Não criaste nenhum álbum.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($albums as $album): ?>
                            <li>
                                <?= htmlspecialchars($album['title']) ?> <span>(<?= date('d/m/Y', strtotime($album['created_at'])) ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
