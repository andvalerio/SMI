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

// Verifica permissões
$stmt = $conn->prepare("SELECT role FROM user_album WHERE user_id = ? AND album_id = ?");
$stmt->bind_param("ii", $userId, $albumId);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

$can_upload = in_array($role, ['Administrador', 'Moderador']);
if (!$can_upload) {
    echo "Acesso negado.";
    exit;
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["images"])) {
    $upload_dir = "uploads/album_$albumId/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    foreach ($_FILES["images"]["tmp_name"] as $i => $tmp_name) {
        if ($_FILES["images"]["error"][$i] === 0) {
            $orig = basename($_FILES["images"]["name"][$i]);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = uniqid() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $filepath)) {
                    $stmt = $conn->prepare("INSERT INTO photo (filename, filepath, upload_by, album_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssii", $filename, $filepath, $userId, $albumId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
    $msg = "Upload concluído.";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Fotos</title>
    <link rel="stylesheet" href="styles/account.css">
    <style>
        .drop-area {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            margin: 20px auto;
            width: 80%;
        }
        .drop-area.dragover {
            background-color: #f0f8ff;
        }
    </style>
</head>
<body>
    <header><strong onclick="location.href='index.php'">Photo Gallery</strong></header>
    <div class="main">
        <div class="sidebar">
            <button onclick="location.href='albuns.php'">🖼️</button>
        </div>
        <div class="center-content">
            <h2>Adicionar Fotos ao Álbum</h2>
            <?php if ($msg) echo "<p class='message'>$msg</p>"; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="drop-area" id="drop-area">
                    Arraste as imagens aqui ou clique para selecionar
                    <input type="file" name="images[]" multiple accept="image/*" style="display:none;" id="fileElem">
                </div>
                <br>
                <input type="submit" value="Enviar">
            </form>
        </div>
    </div>
    <script>
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('fileElem');

        dropArea.addEventListener('click', () => fileInput.click());
        dropArea.addEventListener('dragover', e => {
            e.preventDefault();
            dropArea.classList.add('dragover');
        });
        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));
        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            fileInput.files = e.dataTransfer.files;
            dropArea.classList.remove('dragover');
        });
    </script>
</body>
</html>
