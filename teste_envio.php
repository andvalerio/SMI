<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Upload de Imagens</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }
    </style>
</head>

<body>

    <h2>Enviar Imagens para um Álbum</h2>
    <?php echo date("D-m-Y-H-i"); ?>
    <form action="upload_images.php" method="post" enctype="multipart/form-data">
        Nome do álbum: <input type="text" name="albumname" required><br><br>
        Selecionar imagens: <input type="file" name="images[]" multiple accept="image/*"><br><br>
        <button type="submit">Enviar Imagens</button>
    </form>

</body>

</html>