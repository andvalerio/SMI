<?php
require_once 'includes/session.php';

if (!isLoggedIn()) {
    header('Location: views/auth/login.php');
    exit();
}

header('Location: views/album/homepage.php');
exit();
?>