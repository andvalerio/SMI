<?php
// Verifica se o utilizador tem um determinado role global (não por álbum)
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Garante que o utilizador tem o role indicado global
function requireRole($role)
{
    if (!hasRole($role)) {
        echo "Acesso negado. Permissões insuficientes.";
        exit;
    }
}

// Verifica role num álbum específico
function hasAlbumRole($albumId, $role)
{
    return isset($_SESSION['album_roles'][$albumId]) && $_SESSION['album_roles'][$albumId] === $role;
}

// Garante que o utilizador tem determinado role num álbum específico
function requireAlbumRole($albumId, $role)
{
    if (!hasAlbumRole($albumId, $role)) {
        echo "Acesso negado ao álbum. Permissões insuficientes.";
        exit;
    }
}
