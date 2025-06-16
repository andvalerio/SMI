<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    exit("Acesso negado.");
}

$albumId = intval($_POST['album_id']);
$targetUserId = intval($_POST['user_id']);
$newRole = $_POST['new_role'];

if (!hasAlbumRole($albumId, 'Administrador')) {
    exit("Permissão negada.");
}

$conn = db_connect();
$stmt = $conn->prepare("UPDATE user_album SET role = ? WHERE album_id = ? AND user_id = ?");
$stmt->bind_param("sii", $newRole, $albumId, $targetUserId);
$stmt->execute();
$stmt->close();


// Após atualizar a role verificar se ainda há administradores no álbum
$check_admin_stmt = $conn->prepare("SELECT COUNT(*) FROM user_album WHERE album_id = ? AND role = 'Administrador'");
$check_admin_stmt->bind_param("i", $albumId);
$check_admin_stmt->execute();
$check_admin_stmt->bind_result($adminCount);
$check_admin_stmt->fetch();
$check_admin_stmt->close();

if ($adminCount == 0) {
    // Promover moderador ou utilizador
    $select_stmt = $conn->prepare("SELECT user_id FROM user_album WHERE album_id = ? AND role = 'Moderador' LIMIT 1");
    $select_stmt->bind_param("i", $albumId);
    $select_stmt->execute();
    $select_stmt->bind_result($newAdminId);
    if ($select_stmt->fetch()) {
        $select_stmt->close();
        $update_stmt = $conn->prepare("UPDATE user_album SET role = 'Administrador' WHERE album_id = ? AND user_id = ?");
        $update_stmt->bind_param("ii", $albumId, $newAdminId);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $select_stmt->close();
        $user_stmt = $conn->prepare("SELECT user_id FROM user_album WHERE album_id = ? LIMIT 1");
        $user_stmt->bind_param("i", $albumId);
        $user_stmt->execute();
        $user_stmt->bind_result($newAdminId);
        if ($user_stmt->fetch()) {
            $user_stmt->close();
            $update_stmt = $conn->prepare("UPDATE user_album SET role = 'Administrador' WHERE album_id = ? AND user_id = ?");
            $update_stmt->bind_param("ii", $albumId, $newAdminId);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $user_stmt->close();
        }
    }
}
$conn->close();

header("Location: ../views/album/gerir_participantes.php?album_id=" . $albumId);
exit();
