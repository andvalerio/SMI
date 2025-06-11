<!-- <?php
// session_start();
// require_once 'db.php';

// if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php");
    // exit;
// }

// $conn = db_connect();
// $userId = $_SESSION['user_id'];

// Buscar fotos recentes dos álbuns que o utilizador pode ver
// $query = "
    // SELECT p.filepath, p.upload_at, a.title
    // FROM photo p
    // JOIN album a ON p.album_id = a.id
    // JOIN user_album ua ON ua.album_id = a.id
    // WHERE ua.user_id = ?
    // ORDER BY p.upload_at DESC
    // LIMIT 24
// ";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("i", $userId);
// $stmt->execute();
// $result = $stmt->get_result();
// $photos = $result->fetch_all(MYSQLI_ASSOC);
// $stmt->close();
// $conn->close();
// ?> -->

<!-- 
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Photo Gallery</title>
    <link rel="stylesheet" href="styles/homepage.css">
</head>
<body>
<header>
    <div><strong onclick="location.href='index.php'">Photo Gallery</strong></div>
    <input type="text" placeholder="search">
    <div>
        <button title="Definições">⚙️</button>
        <div class="user-menu">
            <button title="Conta">👤</button>
            <div class="user-dropdown">
                <a href="account.php">Alterar dados da conta</a>
                <a href="logout.php">Terminar sessão</a>
            </div>
        </div>
    </div>
</header>

<div class="main">
    <div class="sidebar">
        <button onclick="location.href='albuns.php'">🖼️</button>
        <button>👍</button>
        <button>👥</button>
    </div>

    <div class="content">
        <h2>Fotos Recentes</h2>
        <div class="photos">
            <?php //foreach ($photos as $photo): ?>
                <div><img src="<? // meter = depois de ? htmlspecialchars($photo['filepath']); ?>" alt="Foto" style="width:100%; height:100%; object-fit:cover;"></div>
            <?php //endforeach; ?>
        </div>
    </div>

    <div class="rightbar"></div>
</div>
</body>
</html> -->

<!--    CODIGO NOVO    -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/session.php';
require_once 'includes/db.php';
session_start();

if (!isLoggedIn()) {
    header('Location: views/auth/login.php');
    exit();
}

header('Location: views/album/list.php');
exit();
?>

<!--
Estrutura do Projeto
├── index.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
│
├── includes/
│   ├── db.php
│   ├── session.php
│   └── auth.php
│
├── models/
│   ├── User.php
│   ├── Album.php
│   └── Photo.php
│
├── controllers/
│   ├── AuthController.php
│   ├── AlbumController.php
│   └── PhotoController.php
│
└── views/
    ├── auth/
    │   ├── login.php
    │   ├── register.php
    │   ├── verify.php
    │   └── account.php
    │
    ├── album/
    │   ├── list.php
    │   ├── create.php
    │   ├── edit.php
    │   └── view.php
    │
    └── photo/
        ├── upload.php
        └── detail.php
-->