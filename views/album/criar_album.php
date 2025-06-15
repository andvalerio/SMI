<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$conn = db_connect();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erros = "";

    // Sanitizar o nome do álbum
    $album_name = trim($_POST["albumname"]);
    $album_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $album_name);

    if (empty($album_name)) {
        $erros .= "Erro: Nome inválido para o título do álbum. <br>";
    } else {
        $description = $_POST["description"];
        $access_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

        $stmt_album = $conn->prepare($query_add_album);
        $stmt_album->bind_param("sssi", $album_name, $description, $access_code, $userId);

        if ($stmt_album->execute()) {
            $album_id = $stmt_album->insert_id;

            // Atribuir o papel de administrador ao criador do álbum
            $role = "Administrador";
            $stmt_user_album = $conn->prepare($query_add_user_album);
            $stmt_user_album->bind_param("iis", $album_id, $userId, $role);
            $stmt_user_album->execute();
        } else {
            $erros .= "Erro ao criar álbum no banco de dados. <br>";
        }
    }

    if (!empty($erros)) {
        echo "<p>$erros</p>";
    } else {
        header("Location: albuns.php");
        exit;
    }
}
$conn->close();

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
    <title>Criar Álbum</title>
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>

    <form action="" method="post">
        <h2>Criar um álbum</h2>

        <p>Nome do álbum:</p>
        <input type="text" name="albumname" required><br><br>

        <p>Descrição do álbum:</p>
        <textarea name="description" rows="4" cols="50" placeholder="Descrição do álbum"></textarea><br><br>

        <button type="submit">Criar Álbum</button>
    </form>

</body>

</html>
