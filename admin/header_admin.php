<?php
require_once 'auth_admin.php';

// Détection automatique de la page active
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Admin - HairRoots' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body style="background:#FDF8F2">

<style>
.dash-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:25px 30px;margin-bottom:30px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.dash-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.8rem;font-weight:900;margin:0}
.dash-header p{color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.88rem}
.dash-nav{display:flex;gap:8px;flex-wrap:wrap}
.dash-nav-btn{background:rgba(201,168,76,0.15);color:#C9A84C;border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:8px 16px;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all 0.3s}
.dash-nav-btn:hover{background:#C9A84C;color:#3E1F0D}
.dash-nav-btn.active{background:#C9A84C;color:#3E1F0D}
</style>

<div class="container" style="padding-top:30px">
    <div class="dash-header">
        <div>
            <h1>
                <?php
                $titres = [
                    'index.php'        => 'Dashboard Admin',
                    'products.php'     => 'Gestion des Produits',
                    'orders.php'       => 'Gestion des Commandes',
                    'users.php'        => 'Gestion des Utilisateurs',
                    'appointments.php' => 'Gestion des RDV',
                    'coiffeuses.php'   => 'Gestion des Coiffeuses',
                    'coiffures.php'    => 'Inspirations Coiffures',
                ];
                echo $titres[$current_page] ?? 'Admin HairRoots';
                ?>
            </h1>
            <p>
                <?php if($current_page === 'index.php'): ?>
                    Bonjour <?= htmlspecialchars($_SESSION['first_name']) ?> · <?= date('d/m/Y') ?>
                <?php else: ?>
                    HairRoots · Espace Administration
                <?php endif; ?>
            </p>
        </div>
        <div class="dash-nav">
            <a href="index.php" class="dash-nav-btn <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="products.php" class="dash-nav-btn <?= $current_page === 'products.php' ? 'active' : '' ?>">
                <i class="bi bi-bag-fill"></i> Produits
            </a>
            <a href="orders.php" class="dash-nav-btn <?= $current_page === 'orders.php' ? 'active' : '' ?>">
                <i class="bi bi-cart-check"></i> Commandes
            </a>
            <a href="users.php" class="dash-nav-btn <?= $current_page === 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
            <a href="appointments.php" class="dash-nav-btn <?= $current_page === 'appointments.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> RDV
            </a>
            <a href="coiffeuses.php" class="dash-nav-btn <?= $current_page === 'coiffeuses.php' ? 'active' : '' ?>">
                <i class="bi bi-scissors"></i> Coiffeuses
            </a>
            <a href="coiffures.php" class="dash-nav-btn <?= $current_page === 'coiffures.php' ? 'active' : '' ?>">
                <i class="bi bi-stars"></i> Inspirations
            </a>
            <a href="/ecommerce/index.php" class="dash-nav-btn" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> Voir le site
            </a>
        </div>
    </div>
</div>