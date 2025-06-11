<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$albumId = intval($_GET['album_id']);
$conn = db_connect();

// Obter o nome do álbum para criar o path
$stmt = $conn->prepare("SELECT a.title, ua.role FROM album a JOIN user_album ua ON a.id = ua.album_id WHERE a.id = ? AND ua.user_id = ?");
$stmt->bind_param("ii", $albumId, $userId);
$stmt->execute();
$stmt->bind_result($album_name, $role);
if (!$stmt->fetch()) {
    echo "Álbum não encontrado ou acesso negado.";
    exit;
}
$stmt->close();

$can_upload = in_array($role, ['Administrador', 'Moderador']);
if (!$can_upload) {
    echo "Acesso negado.";
    exit;
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["images"])) {
    $upload_dir = $CONTENT_FOLDER . $albumId . "/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    foreach ($_FILES["images"]["tmp_name"] as $i => $tmp_name) {
        if ($_FILES["images"]["error"][$i] === 0) {
            $orig = basename($_FILES["images"]["name"][$i]);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $size = $_FILES["images"]["size"][$i];

            if (!in_array($ext, $allowed_extensions)) {
                $msg .= "<p>Arquivo '$orig' tem uma extensão inválida.</p>";
                continue;
            }

            if ($size > $max_file_size) {
                $msg .= "<p>Arquivo '$orig' excede o tamanho máximo de 5MB.</p>";
                continue;
            }

            $filename = uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($tmp_name, $filepath)) {
                $stmt = $conn->prepare("INSERT INTO photo (filename, filepath, upload_by, album_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $filename, $filepath, $userId, $albumId);
                $stmt->execute();
                $stmt->close();
            } else {
                $msg .= "<p>Erro ao mover o arquivo '$orig'.</p>";
            }
        } else {
            $msg .= "<p>Erro no upload do arquivo '$orig'.</p>";
        }
    }

    if ($msg === "") {
        $msg = "Upload concluído com sucesso.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Fotos <?=htmlspecialchars($album_name)?></title>
    <link rel="stylesheet" href="../../assets/styles/account.css">
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
        .message { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <header>
        <div><strong onclick="location.href='../album/homepage.php'">Photo Gallery</strong></div>
        <input type="text" placeholder="search">
        <div>
            <button title="Definições">⚙️</button>
            <div class="user-menu">
                <button title="Conta">👤</button>
                <div class="user-dropdown">
                    <a href="../auth/account.php">Alterar dados da conta</a>
                    <a href="../logout.php">Terminar sessão</a>
                </div>
            </div>
        </div>
    </header>
    <div class="main">
        <div class="sidebar">
            <button onclick="location.href='../album/albuns.php'">🖼️</button>
        </div>
        <div class="center-content">
            <h2>Adicionar Fotos ao Álbum "<?=htmlspecialchars($album_name)?>"</h2>
            <?php if ($msg) echo "<div class='message'>$msg</div>"; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="drop-area" id="drop-area">
                    Arraste as imagens aqui ou clique para selecionar
                    <input type="file" name="images[]" multiple accept="image/*" style="display:none;" id="fileElem">
                </div>
                <div id="preview-container" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;"></div>
                <br>
                <input type="submit" value="Enviar">
            </form>
        </div>
    </div>
    <script>
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('fileElem');
        const previewContainer = document.getElementById('preview-container');

        dropArea.addEventListener('click', () => fileInput.click());

        dropArea.addEventListener('dragover', e => {
            e.preventDefault();
            dropArea.classList.add('dragover');
        });

        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));

        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            dropArea.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            fileInput.files = e.dataTransfer.files;
            showPreview(files);
        });

        fileInput.addEventListener('change', () => {
            showPreview(Array.from(fileInput.files));
        });

        function showPreview(files) {
            previewContainer.innerHTML = ""; // Limpa previews anteriores
            files.forEach(file => {
                if (!file.type.startsWith("image/")) return;

                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.style.maxWidth = "150px";
                    img.style.maxHeight = "150px";
                    img.style.objectFit = "cover";
                    img.style.border = "1px solid #ccc";
                    img.style.borderRadius = "8px";
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>
</html>
