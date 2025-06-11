<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['album_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$albumId = intval($_GET['album_id']);
$conn = db_connect();

// Verificar se é administrador
$stmt = $conn->prepare("SELECT a.title, a.description FROM album a
    JOIN user_album ua ON a.id = ua.album_id
    WHERE ua.user_id = ? AND a.id = ? AND ua.role = 'Administrador'");
$stmt->bind_param("ii", $userId, $albumId);
$stmt->execute();
$stmt->bind_result($title, $description);
$hasAccess = $stmt->fetch();
$stmt->close();

if (!$hasAccess) {
    echo "Acesso negado.";
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = trim($_POST['title']);
    $new_desc = trim($_POST['description']);
    $stmt = $conn->prepare("UPDATE album SET title = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_title, $new_desc, $albumId);
    if ($stmt->execute()) {
        $msg = "Álbum atualizado.";
        $title = $new_title;
        $description = $new_desc;
    } else {
        $msg = "Erro ao atualizar.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Álbum</title>
    <link rel="stylesheet" href="styles/account.css">
</head>
<body>
<header><strong onclick="location.href='index.php'">Photo Gallery</strong></header>
<div class="main">
    <div class="sidebar">
        <button onclick="location.href='albuns.php'">🖼️</button>
    </div>
    <div class="center-content">
        <div class="form-container">
            <h2>Editar Álbum</h2>
            <?php if ($msg) echo "<p class='message'>$msg</p>"; ?>
            <form method="post">
                <label>Título</label>
                <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>
                <label>Descrição</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>
                <input type="submit" value="Guardar Alterações">
            </form>
        </div>
    </div>
</div>
</body>
</html>
