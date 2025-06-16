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
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Notificações</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/styles/main.css">
</head>

<body>
    <?php include_once '../../includes/header.php'; ?>

    <main class="flex-grow-1 p-4">
        <h2 class="text-secondary mb-4">Notificações</h2>
        <?php if (empty($notifications)): ?>
            <p class="text-muted">Sem notificações por agora.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($notifications as $notif): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <?= htmlspecialchars($notif['message']) ?><br>
                            <small class="text-muted">
                                <?= date("d/m/Y H:i", strtotime($notif['created_at'])) ?>
                            </small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>