<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/notifications.php';

if (!isLoggedIn() || !isset($_POST['photo_id'])) {
    header('Location: ../views/auth/login.php');
    exit();
}

$conn = db_connect();

$photoId = intval($_POST['photo_id']);
$photoPath = $_POST['path'] ?? '';
$userId = $_SESSION['user_id'];

// Verificar se já deu like
$stmt = $conn->prepare("SELECT COUNT(*) FROM photo_likes WHERE photo_id = ? AND user_id = ?");
$stmt->bind_param("ii", $photoId, $userId);
$stmt->execute();
$stmt->bind_result($alreadyLiked);
$stmt->fetch();
$stmt->close();

if ($alreadyLiked == 0) {
    $insert = $conn->prepare("INSERT INTO photo_likes (photo_id, user_id) VALUES (?, ?)");
    $insert->bind_param("ii", $photoId, $userId);
    $insert->execute();
    $insert->close();
    // Obter dono da foto
    $stmt = $conn->prepare("
        SELECT upload_by FROM photo WHERE id = ?
    ");
    $stmt->bind_param("i", $photoId);
    $stmt->execute();
    $stmt->bind_result($photoOwnerId);
    $stmt->fetch();
    $stmt->close();

    if ($photoOwnerId != $_SESSION['user_id']) {
        addNotification($photoOwnerId, $_SESSION['username'] . " deu like numa das tuas fotos.");
    }

} else {
    $delete = $conn->prepare("DELETE FROM photo_likes WHERE photo_id = ? AND user_id = ?");
    $delete->bind_param("ii", $photoId, $userId);
    $delete->execute();
    $delete->close();
}

$conn->close();
header("Location: ../views/album/ver_foto.php?path=" . urlencode($photoPath));
exit();
