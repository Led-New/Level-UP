<?php
session_start();

// Se já estiver logado, redirecionar para dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['character_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Se estiver logado mas sem personagem
if (isset($_SESSION['user_id'])) {
    header('Location: criar-personagem.php');
    exit;
}

// Redirecionar para login
header('Location: login.php');
exit;
?>
