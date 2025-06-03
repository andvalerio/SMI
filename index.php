<?php
    session_start();
    require_once 'db.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $conn = db_connect();
    $userId = $_SESSION['user_id'];

    // Buscar os álbuns associados ao utilizador
    $query = "
        SELECT 
            a.id, a.title, a.description,
            (SELECT p.filepath FROM photo p WHERE p.album_id = a.id ORDER BY p.upload_at ASC LIMIT 1) AS cover
        FROM album a
        JOIN user_album ua ON a.id = ua.album_id
        WHERE ua.user_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $albums = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Photo Gallery</title>
    <link rel="stylesheet" href="styles/homepage.css">
    <style>
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            top: 25px;
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 99;
        }

        .user-menu:hover .user-dropdown {
            display: block;
        }

        .user-dropdown a {
            display: block;
            padding: 5px 10px;
            color: black;
            text-decoration: none;
        }

        .user-dropdown a:hover {
            background-color: #f0f0f0;
        }

        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .album-card {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        .album-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header div strong {
            cursor: pointer;
        }
    </style>
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
            <button>🖼️</button>
            <button>👍</button>
            <button>👥</button>
        </div>

        <div class="content">
            <h2>Os teus álbuns</h2>
            <div class="albums-grid">
                <?php if (empty($albums)): ?>
                    <p>Não tens álbuns associados.</p>
                <?php else: ?>
                    <?php foreach ($albums as $album): ?>
                        <div class="album-card">
                            <a href="album.php?id=<?= $album['id'] ?>">
                                <img src="<?= htmlspecialchars($album['cover'] ?? 'images/placeholder.jpg') ?>" alt="Capa do álbum">
                                <p><?= htmlspecialchars($album['title']) ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <div class="rightbar">
            <!-- Conteúdo visível apenas para administradores futuramente -->
        </div>
    </div>
</body>
</html>
