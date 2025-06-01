<?php
session_start();

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login se não estiver autenticado
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>App Name</title>
    <link rel="stylesheet" href="styles/homepage.css">
</head>

<body>
    <header>
        <div><strong>App Name</strong></div>
        <input type="text" placeholder="search">
        <p><a href="logout.php">Terminar sessão</a></p>
    </header>

    <div class="main">

        <div class="sidebar">
            <!-- 

            ICONS NOS BOTOES

            -->
            <button>🏠</button>
            <button>🖼️</button>
            <button>👍</button>
            <button>👥</button>
            <button>⚙️</button>
        </div>

        <div class="content">
            <div class="album-info">
                <div class="album-cover"></div>
                <!-- 
                
                INFO A SACAR DA DB:

                NUMERO DO ALBUM
                NUMERO DA FOTO
                UTILIZADORES INSCRITOS NO ALBUM 
                
                -->
                <h2>Album 1</h2>
                <p>Número de fotos: 12</p>
                <p>Número de utilizadores: 4</p>
            </div>
            <!-- 
            
            ARRANJAR MANEIRA DE MOSTRAR AQUI AS FOTOS 
            
            -->
            <div class="photos">
                <?php for ($i = 0; $i < 12; $i++): ?>
                    <div></div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="rightbar">
            <!-- 
            
            BARRA LATERAL DIREITA

            ALBUNS DISPONIVEIS NA VISAO DO ADMINISTRADOR
            PARA USERS NORMAIS, NAO MOSTRAR
            
            -->
            <?php

            ?>
        </div>

    </div>
</body>

</html>