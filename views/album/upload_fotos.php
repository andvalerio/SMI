<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/notifications.php';

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

$can_upload = in_array($role, ['Administrador', 'Moderador', 'Utilizador']);
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

        $albumId = $_POST['album_id'] ?? null;
        $uploadBy = $_SESSION['user_id'];

        // Obter título do álbum
        $albumTitleStmt = $conn->prepare("SELECT title FROM album WHERE id = ?");
        $albumTitleStmt->bind_param("i", $albumId);
        $albumTitleStmt->execute();
        $albumTitleStmt->bind_result($albumTitle);
        $albumTitleStmt->fetch();
        $albumTitleStmt->close();

        // Notificar os outros utilizadores do álbum
        $notifyStmt = $conn->prepare("SELECT user_id FROM user_album WHERE album_id = ? AND user_id != ?");
        $notifyStmt->bind_param("ii", $albumId, $uploadBy);
        $notifyStmt->execute();
        $notifyStmt->bind_result($otherUserId);
        while ($notifyStmt->fetch()) {
            addNotification($otherUserId, "Foram adicionadas novas fotos ao álbum \"$albumTitle\".");
        }
        $notifyStmt->close();
    }
}

$notificacao_count = 0;
if (isLoggedIn()) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($notificacao_count);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Fotos <?=htmlspecialchars($album_name)?></title>
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>
<body>
    <header>
        <div><strong onclick="location.href='homepage.php'">Photo Gallery</strong></div>
        <div>
            <button title="Notificações" onclick="location.href='notificacoes.php'">
            🔔  <?= $notificacao_count > 0 ? "($notificacao_count)" : "" ?>
            </button>
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
        <button onclick="location.href='albuns.php'">🖼️</button>
        <button onclick="location.href='likes.php'">👍</button>
    </div>
        <div class="center-content">
            <div class="form-container">
                <button style="margin-bottom: 15px;" onclick="location.href='album.php?id=<?= $albumId ?>'">← Voltar ao Álbum</button>
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
