<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

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
    $conn->close();
}

header("Location: ../views/album/ver_foto.php?path=" . urlencode($photoPath));
exit();

