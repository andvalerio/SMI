<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_POST['photo_id'], $_POST['comment_user'], $_POST['created_at'], $_POST['path'])) {
    header("Location: ../views/pages/ver_foto.php?path=" . urlencode($_POST['path']));
    exit;
}

$photoId = $_POST['photo_id'];
$commentUser = $_POST['comment_user'];
$createdAt = $_POST['created_at'];
$path = $_POST['path'];

$conn = db_connect();

// Obter o ID do autor do comentário
$stmt = $conn->prepare("SELECT u.id, p.album_id FROM photo_comments pc JOIN user u ON pc.user_id = u.id JOIN photo p ON pc.photo_id = p.id WHERE pc.photo_id = ? AND u.username = ? AND pc.created_at = ?");
$stmt->bind_param("iss", $photoId, $commentUser, $createdAt);
$stmt->execute();
$stmt->bind_result($commentUserId, $albumId);

if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    header("Location: ../views/pages/ver_foto.php?path=" . urlencode($path));
    exit;
}
$stmt->close();

// Verifica permissões
$loggedUserId = $_SESSION['user_id'] ?? null;
if (!$loggedUserId) {
    header("Location: ../views/pages/ver_foto.php?path=" . urlencode($path));
    exit;
}

// Pode apagar se for o dono ou tiver papel elevado
$isOwner = $loggedUserId === $commentUserId;
$isModeratorOrAdmin = hasAlbumRole($albumId, 'Moderador') || hasAlbumRole($albumId, 'Administrador');

if ($isOwner || $isModeratorOrAdmin) {
    $deleteStmt = $conn->prepare("DELETE FROM photo_comments WHERE photo_id = ? AND user_id = ? AND created_at = ?");
    $deleteStmt->bind_param("iis", $photoId, $commentUserId, $createdAt);
    $deleteStmt->execute();
    $deleteStmt->close();
}

$conn->close();
header("Location: ../views/album/ver_foto.php?path=" . urlencode($path));
exit;
