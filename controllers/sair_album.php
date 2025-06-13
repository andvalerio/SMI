<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['album_id'])) {
    header("Location: ../views/albuns.php");
    exit();
}

$userId = $_SESSION['user_id'];
$albumId = intval($_POST['album_id']);
$conn = db_connect();

// Verifica se o utilizador está no álbum e qual a sua role
$stmt = $conn->prepare("SELECT role FROM user_album WHERE album_id = ? AND user_id = ?");
$stmt->bind_param("ii", $albumId, $userId);
$stmt->execute();
$stmt->bind_result($currentRole);
if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    $_SESSION['error_message'] = "Não participa neste álbum.";
    header("Location: ../views/albuns.php");
    exit();
}
$stmt->close();

// Remove o utilizador do álbum
$delete_stmt = $conn->prepare("DELETE FROM user_album WHERE album_id = ? AND user_id = ?");
$delete_stmt->bind_param("ii", $albumId, $userId);
$delete_stmt->execute();
$delete_stmt->close();

// Verifica se ainda existe um administrador no álbum
$check_admin_stmt = $conn->prepare("SELECT COUNT(*) FROM user_album WHERE album_id = ? AND role = 'Administrador'");
$check_admin_stmt->bind_param("i", $albumId);
$check_admin_stmt->execute();
$check_admin_stmt->bind_result($adminCount);
$check_admin_stmt->fetch();
$check_admin_stmt->close();

if ($adminCount == 0) {
    // Promover o primeiro moderador
    $select_stmt = $conn->prepare("
        SELECT user_id FROM user_album 
        WHERE album_id = ? AND role = 'Moderador' LIMIT 1
    ");
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
        // Se não houver moderadores, promover primeiro utilizador
        $user_stmt = $conn->prepare("
            SELECT user_id FROM user_album 
            WHERE album_id = ? LIMIT 1
        ");
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

$_SESSION['success_message'] = "Saiu do álbum com sucesso.";
header("Location: ../views/album/albuns.php");
exit();
