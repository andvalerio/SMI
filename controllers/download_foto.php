<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isset($_POST['photo_id'], $_POST['path']) || !isset($_SESSION['user_id'])) {
    echo "Acesso inválido.";
    exit;
}

$photoId = intval($_POST['photo_id']);
$path = $_POST['path'];
$userId = $_SESSION['user_id'];

$conn = db_connect();

// Registrar o download
$insertStmt = $conn->prepare("INSERT INTO photo_downloads (download_at, photo_id, user_id) VALUES (NOW(), ?, ?)");
$insertStmt->bind_param("ii", $photoId, $userId);
$insertStmt->execute();
$insertStmt->close();

$conn->close();

// Alterar o caminho para o ficheiro real a partir do download_foto
$relativePath = preg_replace('#^(\.\./)+#', '', $path); // remove todos os ../ do início
$fullPath = realpath(__DIR__ . '/../' . $relativePath);

if ($fullPath && file_exists($fullPath)) {
    $filename = basename($fullPath);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    echo "Ficheiro não encontrado.";
}
