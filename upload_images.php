<?php

$CONTENT_FOLDER = "content/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erros = "Erros: ";

    // garantir que o nome nao tem espaços ou caracteres diferentes
    $album_name = trim($_POST["albumname"]);
    $album_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $album_name);

    if ($album_name == null) {
        $erros .= "Nome inválido para título de álbum. <br>";
    } else {
        $album_folder = $CONTENT_FOLDER . $album_name . "/";

        $exist_folder = is_dir($album_folder);
        if (!$exist_folder) {
            // criar a pasta se nao existir
            if (!mkdir($album_folder, 0755, true)) {
                $erros .= "Não foi possível criar a pasta do álbum. <br>";
            }
        }


        // PROCESSAR MULTIPLAS FOTOS ENVIADAS ATRAVÉS DE UM FORMULARIO
        if (isset($_FILES["images"]) && !empty($_FILES["images"]["tmp_name"][0])) {
            foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
                if ($_FILES["images"]["error"][$key] !== 0) {
                    $erros .= "Erro ao enviar o ficheiro #" . ($key + 1) . "<br>";
                    continue;
                }

                $fileName = basename($_FILES["images"]["name"][$key]);
                $imagePath = $album_folder . time() . "_" . $fileName;
                $imageFileType = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                $allowedTypes = ["jpg", "jpeg", "png", "gif"];

                if (in_array($imageFileType, $allowedTypes)) {
                    if (move_uploaded_file($tmp_name, $imagePath)) {
                        $uploadedImages[] = $imagePath;
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
    }
}

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Resultado do Upload</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        .gallery img {
            width: 150px;
            margin: 5px;
            border: 1px solid #ccc;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }
    </style>
</head>

<body>

    <h2>Resultado do Upload</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <li><?= implode("</li><li>", $errors) ?></li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($uploadedImages)): ?>
        <div class="success">
            <p>Imagens carregadas com sucesso para o álbum <strong><?= htmlspecialchars($album_name) ?></strong>!</p>
        </div>
        <div class="gallery">
            <?php foreach ($uploadedImages as $img): ?>
                <img src="<?= $img ?>" alt="Imagem">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p><a href="formulario.html">Voltar</a></p>

</body>

</html>