<?php
require_once '../../includes/db.php';

function getRoleAndAlbumName($conn, $userId, $albumId)
{
    $album_name = "";
    $role = null;

    // Obter o nome do álbum para criar o path
    $stmt = $conn->prepare("SELECT a.title, ua.role FROM album a JOIN user_album ua ON a.id = ua.album_id WHERE a.id = ? AND ua.user_id = ?");
    $stmt->bind_param("ii", $albumId, $userId);
    $stmt->execute();
    $stmt->bind_result($album_name, $role);
    if (!$stmt->fetch()) {
        echo "Álbum não encontrado ou acesso negado.";
        return [null, null];
    }
    $stmt->close();
    return [$album_name, $role];
}


function upload($conn, $albumId, $userId)
{
    $CONTENT_FOLDER = "../../assets/content/";
    $max_file_size = 50 * 1024 * 1024; // 50 MB
    $allowed_extensions_photo = ["jpg", "jpeg", "png", "gif"];
    $allowed_extensions_video = ["mp4", "mov"];

    $upload_dir = $CONTENT_FOLDER . $albumId . "/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true); // criar pasta no content se n existir

    foreach ($_FILES["media"]["tmp_name"] as $i => $tmp_name) {
        if ($_FILES["media"]["error"][$i] === 0) {
            $orig = basename($_FILES["media"]["name"][$i]);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $size = $_FILES["media"]["size"][$i];

            if (!in_array($ext, array_merge($allowed_extensions_photo, $allowed_extensions_video))) {
                return "<p>Extensão inválida: '$orig'.</p>";
                
            }

            if ($size > $max_file_size) {
                return "<p>Ficheiro muito grande: '$orig'.</p>";
            }

            $filename = uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($tmp_name, $filepath)) {

                if (in_array(strtolower(pathinfo($ext, PATHINFO_EXTENSION)), $allowed_extensions_video)) {
                    $is_video = true;
                } else {
                    $is_video = false;
                }

                $stmt = $conn->prepare("INSERT INTO photo (filename, filepath, upload_by, album_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $filename, $filepath, $userId, $albumId);
                $stmt->execute();
                $stmt->close();
            } else {
                return "<p>Erro ao mover o ficheiro '$orig'.</p>";
            }
        }
    }
    return "";
}
