<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/notifications.php';

if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$albumId = intval($_POST['album_id']);
$identifier = trim($_POST['user_identifier']);
$role = $_POST['role'];
$albumTitle = $_SESSION['album_title'] ?? 'Álbum';

// Verifica se o utilizador atual é administrador do álbum
if (!hasAlbumRole($albumId, 'Administrador')) {
    die("Sem permissões.");
}

$conn = db_connect();

// Procurar utilizador por username ou email
$stmt = $conn->prepare("SELECT id FROM user WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$stmt->bind_result($newUserId);
if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    $_SESSION['success_message'] = "Utilizador não encontrado.";
    header("Location: ../views/album/album.php?id=$albumId");
    exit();
}
$stmt->close();

// Verificar se já está associado ao álbum
$check_stmt = $conn->prepare("SELECT 1 FROM user_album WHERE album_id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $albumId, $newUserId);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    $conn->close();
    $_SESSION['success_message'] = "Utilizador já está no álbum.";
    header("Location: ../views/album/album.php?id=$albumId");
    exit();
}
$check_stmt->close();

// Inserir relação
$insert_stmt = $conn->prepare("INSERT INTO user_album (album_id, user_id, role) VALUES (?, ?, ?)");
$insert_stmt->bind_param("iis", $albumId, $newUserId, $role);
$insert_stmt->execute();
$insert_stmt->close();

// Notificar o utilizador adicionado
addNotification($newUserId, "Foste adicionado ao álbum \"$albumTitle\".");

// Notificar os outros membros
$notifyStmt = $conn->prepare("SELECT user_id FROM user_album WHERE album_id = ? AND user_id != ? AND user_id != ?");
$notifyStmt->bind_param("iii", $albumId, $userId, $newUserId);
$notifyStmt->execute();
$notifyStmt->bind_result($otherUserId);
while ($notifyStmt->fetch()) {
    addNotification($otherUserId, "Um novo utilizador foi adicionado ao álbum \"$albumTitle\".");
}
$notifyStmt->close();
$conn->close();

$_SESSION['success_message'] = "Utilizador adicionado com sucesso.";
header("Location: ../views/album/album.php?id=$albumId");
exit();
