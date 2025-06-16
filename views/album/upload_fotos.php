<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/notifications.php';
require_once '../../controllers/uploads.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$albumId = intval($_GET['album_id']);
$conn = db_connect();

[$album_name, $role] = getRoleAndAlbumName($conn, $userId, $albumId);
if ($album_name == "" || $role == null) {
    echo "Erro ao obter nome do álbum ou papel do utilizador.";
    exit;
}

$can_upload = in_array($role, ['Administrador', 'Moderador', 'Utilizador']);
if (!$can_upload) {
    echo "Acesso negado.";
    exit;
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["media"])) {
    $msg = upload($conn, $albumId, $userId);

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

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Adicionar Fotos <?= htmlspecialchars($album_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="container py-5">
        <div class="justify-content-center">
            <div class="card shadow-sm p-4">
                <h3 class="mb-4">Adicionar Fotos ao Álbum "<?= htmlspecialchars($album_name) ?>"</h3>

                <?php if ($msg): ?>
                    <div class="alert alert-info"> <?= $msg ?> </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div id="drop-area" class="border border-secondary border-dashed rounded p-4 text-center" style="cursor:pointer">
                        <p class="mb-0">Arraste as imagens aqui ou clique para selecionar</p>
                        <input type="file" name="media[]" multiple accept="image/*,video/*" class="form-control d-none" id="fileElem">
                    </div>

                    <div id="preview-container" class="d-flex flex-wrap gap-2 mt-3"></div>
                    <button type="submit" class="btn btn-primary mt-4 mb-3 mx-auto d-block">Enviar</button>
                </form>

                <a class="btn btn-link mt-2" href="album.php?id=<?= intval($_GET['album_id']) ?>">Voltar atrás</a>
            </div>
        </div>
    </main>

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
                const ext = file.name.split('.').pop().toLowerCase();

                const videoExts = ["mp4", "mov"];
                const imageExts = ["jpg", "jpeg", "png", "gif"];

                if (imageExts.includes(ext)) {
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
                } else if (videoExts.includes(ext)) {
                    const video = document.createElement("video");
                    video.controls = true;
                    video.style.maxWidth = "150px";
                    video.style.maxHeight = "150px";
                    video.style.border = "1px solid #ccc";
                    video.style.borderRadius = "8px";

                    const reader = new FileReader();
                    reader.onload = e => {
                        video.src = e.target.result;
                        previewContainer.appendChild(video);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>