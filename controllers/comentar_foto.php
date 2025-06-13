<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/notifications.php';

if (!isLoggedIn() || !isset($_POST['photo_id'], $_POST['comment'])) {
    header('Location: ../views/auth/login.php');
    exit();
}

$photoId = intval($_POST['photo_id']);
$comment = trim($_POST['comment']);
$photoPath = $_POST['path'] ?? '';
$userId = $_SESSION['user_id'];

if ($comment !== '') {
    $conn = db_connect();
    $stmt = $conn->prepare("INSERT INTO photo_comments (photo_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $photoId, $userId, $comment);
    $stmt->execute();
    $stmt->close();
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
        addNotification($photoOwnerId, $_SESSION['username'] . ' comentou numa das tuas fotos: "' . htmlspecialchars($comment) . '"');
    }

    $conn->close();
}

header("Location: ../views/album/ver_foto.php?path=" . urlencode($photoPath));
exit();

