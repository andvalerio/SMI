<?php
session_start();

// Verifica se o utilizador está autenticado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login se não estiver autenticado
    header("Location: login.php");
    exit;
}

// Query à base de dados para buscar dados do utilizador se necessário.
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Área Privada</title>
</head>
<body>
    <h1>Bem-vindo à tua área privada!</h1>
    <p>Estás autenticado com sucesso.</p>

    <p><a href="logout.php">Terminar sessão</a></p>
</body>
</html>
