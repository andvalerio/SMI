<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    exit("Acesso negado.");
}

$adminId = $_SESSION['user_id'];
$albumId = intval($_POST['album_id']);
$targetUserId = intval($_POST['user_id']);

if (!hasAlbumRole($albumId, 'Administrador')) {
    exit("Permissão negada.");
}

$conn = db_connect();
$stmt = $conn->prepare("DELETE FROM user_album WHERE album_id = ? AND user_id = ?");
$stmt->bind_param("ii", $albumId, $targetUserId);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: ../views/album/gerir_participantes.php?album_id=" . $albumId);
exit();
