<?php
// Verifica se o utilizador tem um determinado role
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Garante que o utilizador tem o role indicado
function requireRole($role)
{
    if (!hasRole($role)) {
        echo "Acesso negado. Permissões insuficientes.";
        exit;
    }
}
