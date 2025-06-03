<?php
#    <?php echo date("D-m-Y-H-i");
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = db_connect();
$userId = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erros = "";

    // garantir que o nome nao tem espaços ou caracteres diferentes
    $album_name = trim($_POST["albumname"]);
    $album_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $album_name);

    if ($album_name == null) {
        $erros .= "Erros: Nome inválido para título de álbum. <br>";
    } else {
        $album_folder = $CONTENT_FOLDER . $album_name . "/";

        $exist_folder = is_dir($album_folder);
        if (!$exist_folder) {
            // criar a pasta se nao existir
            if (!mkdir($album_folder, 0755, true)) {
                $erros .= "Não foi possível criar a pasta do álbum. <br>";
            }
        }

        $description = $_POST["description"];
        $access_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

        $stmt_album = $conn->prepare($query_add_album);
        $stmt_album->bind_param("sssi", $album_name, $description, $access_code, $userId);

        if ($stmt_album->execute()) {
            $album_id = $stmt_album->insert_id;

            // associar utilizador a álbum com role máxima.
            $role = "Administrador";
            $stmt_user_album = $conn->prepare($query_add_user_album);
            $stmt_user_album->bind_param("iis", $album_id, $userId, $role);

            $stmt_user_album->execute();

            // PROCESSAR MULTIPLAS FOTOS ENVIADAS ATRAVÉS DE UM FORMULARIO
            if (isset($_FILES["images"]) && !empty($_FILES["images"]["tmp_name"][0])) {
                foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
                    if ($_FILES["images"]["error"][$key] !== 0) {
                        $erros .= "Erro ao enviar o ficheiro #" . ($key + 1) . "<br>";
                        continue;
                    }

                    $fileName = basename($_FILES["images"]["name"][$key]);
                    $nome_sem_extensao = pathinfo($fileName, PATHINFO_FILENAME);
                    $extensao = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $fileName_db = $nome_sem_extensao . "_" . date('Y-m-d-H-i') . "." . $extensao;

                    $caminho_completo = $album_folder . $fileName_db;

                    if (in_array($extensao, $allowed_extensions)) {
                        if (move_uploaded_file($tmp_name, $caminho_completo)) {

                            // Inserir na tabela photo
                            $query_add_photo = "INSERT INTO photo (filename, filepath, upload_by, album_id) VALUES (?, ?, ?, ?)";
                            $stmt_photo = $conn->prepare($query_add_photo);
                            $stmt_photo->bind_param("ssii", $fileName_db, $caminho_completo, $userId, $album_id);
                            $stmt_photo->execute();
                        } else {
                            $erros .= "Erro ao mover o ficheiro '$fileName'. <br>";
                        }
                    } else {
                        $erros .= "Tipo de ficheiro '$fileName' não permitido. <br>";
                    }
                }
            } else {
                $erros .= "Nenhuma foto enviada. <br>";
            }
        } else {
            $erros .= "Erro ao criar álbum. <br>";
        }
    }

    if (!empty($erros)) {
        echo "<p>$erros</p>";
    } else {
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Upload de Imagens</title>

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    </style>
</head>

<body>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <li><?= implode("</li><li>", $errors) ?></li>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (empty($errors)): ?>
        <div class="error">
            <ul>
                <li>Não houve problema a adicionar tudo</li>
            </ul>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <h2>Criar um álbum</h2>

        <p>Nome do álbum:</p>
        <input type="text" name="albumname" required><br><br>

        <p>Selecionar imagens:</p>
        <input type="file" name="images[]" multiple accept="image/*"><br><br>

        <button type="submit">Enviar Imagens</button>
    </form>

</body>

</html>