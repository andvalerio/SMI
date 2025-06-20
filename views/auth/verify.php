<?php
require_once '../../includes/db.php';
$conn = db_connect();

$token = $_GET['token'] ?? null;
$verificado = false;

if ($token) {
    $stmt = $conn->prepare("SELECT id FROM user WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId);
        $stmt->fetch();

        $update = $conn->prepare("UPDATE user SET valid = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("i", $userId);
        $update->execute();
        $update->close();

        $verificado = true;
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Verificação</title>
</head>
<body>
  <h2><?php echo $verificado ? "Conta verificada com sucesso!" : "Token inválido ou expirado."; ?></h2>
  <a href="login.php">Fazer login</a>
</body>
</html>
