<?php
// Sécurité : à inclure en haut de CHAQUE page admin
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}
?>