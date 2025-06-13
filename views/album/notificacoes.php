<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

$conn = db_connect();
$userId = $_SESSION['user_id'];

// Marcar como lidas
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $userId");

$stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notificações</title>
    <link rel="stylesheet" href="../../assets/styles/homepage.css">
</head>
<body>
<header>
    <div><strong onclick="location.href='homepage.php'">Photo Gallery</strong></div>
    <div>
        <button onclick="location.href='notificacoes.php'">🔔</button>
        <div class="user-menu">
            <button>👤</button>
            <div class="user-dropdown">
                <a href="../auth/account.php">Alterar dados da conta</a>
                <a href="../logout.php">Terminar sessão</a>
            </div>
        </div>
    </div>
</header>

<div class="main">
    <div class="content">
        <h2>Notificações</h2>
        <?php if (empty($notifications)): ?>
            <p>Sem notificações por agora.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($notifications as $notif): ?>
                    <li>
                        <?= htmlspecialchars($notif['message']) ?><br>
                        <small><?= date("d/m/Y H:i", strtotime($notif['created_at'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
