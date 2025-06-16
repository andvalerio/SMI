<?php
if (!isset($_SESSION)) session_start();

require_once 'db.php';
require_once 'session.php';

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

<!-- Google Translate Script -->
<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'pt',
            includedLanguages: 'en,es,fr,de,pt',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE
        }, 'google_translate_element');
    }
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<header class="d-flex justify-content-between align-items-center px-4">
    <strong onclick="location.href='homepage.php'" class="fs-4" style="cursor:pointer">Photo Gallery</strong>

    <div class="d-flex align-items-center gap-2">
        <!-- Google Translate dropdown -->
        <div id="google_translate_element" class="me-2"></div>

        <!-- Botões pa ver albuns -->
        <button class="btn btn-light btn-sm" onclick="location.href='albuns.php'" title="Álbuns">
            <i class="bi bi-images"></i>
        </button>

        <!-- Botões pa ver likes -->
        <button class="btn btn-light btn-sm" onclick="location.href='likes.php'" title="Likes">
            <i class="bi bi-heart-fill"></i>
        </button>

        <!-- Botão de notificações -->
        <button class="btn btn-light btn-sm position-relative" onclick="location.href='notificacoes.php'" title="Notificações">
            <i class="bi bi-bell-fill"></i>
            <?php if ($notificacao_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $notificacao_count ?>
                </span>
            <?php endif; ?>
        </button>

        <!-- Dropdown de utilizador -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Conta">
                <i class="bi bi-person-circle"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="../auth/account.php">Alterar dados da conta</a></li>
                <li><a class="dropdown-item" href="../logout.php">Terminar sessão</a></li>
            </ul>
        </div>
    </div>
</header>
