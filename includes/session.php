<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /views/auth/login.php');
        exit();
    }
}

function logout() {
    session_destroy();
    header('Location: /views/auth/login.php');
    exit();
}