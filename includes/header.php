<?php
// La session est gérée par database.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'HairRoots'; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- CSS HairRoots -->
    <link rel="stylesheet" href="/ecommerce/assets/css/style.css">
</head>
<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="container d-flex justify-content-between align-items-center py-1">
            <span class="small">🌍 Livraison partout en France sous 48h</span>
            <span class="small">📞 Support 7j/7 | ✉️ contact@hairroots.fr</span>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg sticky-top hairroots-nav">
        <div class="container">
            <!-- LOGO -->
            <a class="navbar-brand hairroots-brand" href="/ecommerce/index.php">
                🌿 Hair<span>Roots</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list text-white fs-2"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto gap-1">
                    <li class="nav-item">
                        <a class="nav-link hairroots-link" href="/ecommerce/index.php">
                            <i class="bi bi-house-fill"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link hairroots-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-scissors"></i> Mèches
                        </a>
                        <ul class="dropdown-menu hairroots-dropdown">
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?category=1">🌀 Cheveux Bouclés</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?category=2">✨ Cheveux Crépus</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?category=3">💫 Cheveux Lisses</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?category=4">🌊 Cheveux Ondulés</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link hairroots-link" href="/ecommerce/products/index.php?category=5">
                            <i class="bi bi-droplet-fill"></i> Soins
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link hairroots-link" href="/ecommerce/coiffures.php">
                            <i class="bi bi-image"></i> Inspirations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link hairroots-link" href="/ecommerce/coiffeuses.php">
                            <i class="bi bi-people-fill"></i> Nos Coiffeuses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link hairroots-link nav-rdv" href="/ecommerce/rendez-vous.php">
                            📅 Prendre RDV
                        </a>
                    </li>
                </ul>

                <!-- ICONES DROITE -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Recherche -->
                    <a href="#" class="nav-icon" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="bi bi-search"></i>
                    </a>
                    <!-- Wishlist -->
                    <a href="/ecommerce/wishlist.php" class="nav-icon">
                        <i class="bi bi-heart"></i>
                    </a>
                    <!-- Panier -->
                    <a href="/ecommerce/cart/index.php" class="nav-icon position-relative">
                        <i class="bi bi-bag"></i>
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                    <!-- Compte -->
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a class="nav-icon dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end hairroots-dropdown">
                                <li><span class="dropdown-item-text fw-bold text-brown">👋 <?php echo $_SESSION['first_name']; ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ecommerce/user/profile.php"><i class="bi bi-person"></i> Mon Profil</a></li>
                                <li><a class="dropdown-item" href="/ecommerce/user/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/ecommerce/user/login.php" class="btn btn-outline-gold btn-sm ms-1">
                            <i class="bi bi-box-arrow-in-right"></i> Connexion
                        </a>
                        <a href="/ecommerce/user/register.php" class="btn btn-gold btn-sm">
                            <i class="bi bi-person-plus"></i> Inscription
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- MODAL RECHERCHE -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-body p-4">
                    <h5 class="mb-3" style="font-family:'Playfair Display',serif;color:#3E1F0D;">🔍 Rechercher un produit</h5>
                    <form action="/ecommerce/products/index.php" method="GET">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control border-0 bg-light" name="search" 
                                   placeholder="Mèches bouclées, shampoing, huile...">
                            <button class="btn btn-gold px-4" type="submit">Rechercher</button>
                        </div>
                    </form>
                    <div class="mt-3">
                        <span class="text-muted small">Suggestions : </span>
                        <a href="/ecommerce/products/index.php?category=1" class="badge bg-light text-dark text-decoration-none me-1">🌀 Bouclés</a>
                        <a href="/ecommerce/products/index.php?category=2" class="badge bg-light text-dark text-decoration-none me-1">✨ Crépus</a>
                        <a href="/ecommerce/products/index.php?category=5" class="badge bg-light text-dark text-decoration-none me-1">🌿 Soins</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<main>