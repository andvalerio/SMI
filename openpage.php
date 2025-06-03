<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = db_connect();
$userId = $_SESSION['user_id'];

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Gallery</title>
    <link rel="stylesheet" href="styles/openpage.css">
</head>

<body>
    <button onclick="location.href='criar_album.php'">Criar um álbum</button>
    <button>Entrar num álbum</button>
</body>

</html>