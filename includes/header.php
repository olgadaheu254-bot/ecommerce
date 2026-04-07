<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'HairRoots'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/ecommerce/assets/css/style.css">
</head>
<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="container d-flex justify-content-between align-items-center py-1">
            <span class="small"> Livraison partout en France sous 48h</span>
            <span class="small"> Support 7j/7 | ✉️ contact@hairroots.fr</span>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg sticky-top hairroots-nav" style="background:#F5E6D3 !important;">
        <div class="container">

            <!-- LOGO -->
            <a class="navbar-brand hairroots-brand" href="/ecommerce/index.php">
                <svg height="70" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .nr { stroke: #C9A84C; fill: none; }
                        .lh { fill: #F5D78A; }
                        .lr { fill: #F5D78A; }
                        .dm { fill: #C9A84C; }
                        .sl { stroke: #C9A84C; fill: none; } 
                    </style>
                    <circle cx="100" cy="100" r="88" class="nr" stroke-width="1.5"/>
                    <circle cx="100" cy="100" r="80" stroke="#8B6914" fill="none" stroke-width="0.8"/>
                    <line x1="72" y1="30" x2="128" y2="30" class="nr" stroke-width="1"/>
                    <polygon class="dm" points="100,23 104,28 100,33 96,28"/>
                    <polygon class="dm" points="72,30 76,34 72,38 68,34"/>
                    <polygon class="dm" points="128,30 132,34 128,38 124,34"/>
                    <line x1="72" y1="30" x2="61" y2="21" class="nr" stroke-width="0.7"/>
                    <line x1="128" y1="30" x2="139" y2="21" class="nr" stroke-width="0.7"/>
                    <polygon class="dm" points="100,167 104,172 100,177 96,172"/>
                    <polygon class="dm" points="13,100 17,104 13,108 9,104"/>
                    <polygon class="dm" points="187,100 191,104 187,108 183,104"/>
                    <rect class="lh" x="52" y="62" width="14" height="66" rx="1.5"/>
                    <rect class="lh" x="90" y="62" width="14" height="66" rx="1.5"/>
                    <rect class="lh" x="52" y="91" width="52" height="11" rx="1.5"/>
                    <rect class="lh" x="46" y="59" width="23" height="5" rx="1"/>
                    <rect class="lh" x="46" y="124" width="23" height="5" rx="1"/>
                    <rect class="lh" x="87" y="59" width="23" height="5" rx="1"/>
                    <rect class="lh" x="87" y="124" width="23" height="5" rx="1"/>
                    <line x1="113" y1="67" x2="113" y2="133" stroke="#C9A84C" fill="none" stroke-width="0.8"/>
                    <rect class="lr" x="120" y="62" width="14" height="66" rx="1.5"/>
                    <rect class="lr" x="120" y="62" width="32" height="10" rx="1.5"/>
                    <rect class="lr" x="120" y="90" width="28" height="10" rx="1.5"/>
                    <path class="lr" d="M134 72 Q154 72 154 81 Q154 91 134 91 L146 91 Q166 91 166 81 Q166 72 146 72 Z"/>
                    <path class="lr" d="M134 100 L146 100 L166 129 L153 129 L134 100 Z"/>
                    <rect class="lr" x="114" y="59" width="23" height="5" rx="1"/>
                    <rect class="lr" x="114" y="124" width="23" height="5" rx="1"/>
                    <path class="sl" stroke-width="1.5" stroke-linecap="round" d="M46,110 C52,113 62,114 72,112 C84,110 95,108 105,109 C115,110 124,111 136,110 C145,109 152,108 160,110"/>
                    <path class="sl" stroke-width="1" stroke-linecap="round" opacity="0.7" d="M46,110 C42,106 40,102 42,98 C44,96 47,98 46,103"/>
                    <path class="sl" stroke-width="0.8" stroke-linecap="round" opacity="0.5" d="M160,110 C164,109 168,108 170,110 C171,112 169,114 166,113"/>
                </svg>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list text-white fs-2"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto gap-1">

                    <!-- Accueil -->
                    <li class="nav-item">
                        <a class="nav-link hairroots-link" href="/ecommerce/index.php">
                            <i class="bi bi-house-fill"></i> Accueil
                        </a>
                    </li>

                    <!-- Femmes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link hairroots-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-dress"></i> Femmes
                        </a>
                        <ul class="dropdown-menu hairroots-dropdown">
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?type=femme&sub=soins">
                                <i class="bi bi-droplet-fill me-2"></i> Soins de cheveux</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/coiffures.php?type=femme">
                                <i class="bi bi-scissors me-2"></i> Coiffure</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?type=femme&sub=meches">
                                <i class="bi bi-bag-fill me-2"></i> Achat des mèches</a></li>
                        </ul>
                    </li>

                    <!-- Hommes -->
                    <li class="nav-item dropdown">
                        <a class="nav-link hairroots-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-fill"></i> Hommes
                        </a>
                        <ul class="dropdown-menu hairroots-dropdown">
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?type=homme&sub=soins">
                                <i class="bi bi-droplet-fill me-2"></i> Soins de cheveux</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/coiffures.php?type=homme">
                                <i class="bi bi-scissors me-2"></i> Coiffure</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?type=homme&sub=meches">
                                <i class="bi bi-bag-fill me-2"></i> Achat des mèches</a></li>
                        </ul>
                    </li>

                    <!-- Enfants -->
                    <li class="nav-item dropdown">
                        <a class="nav-link hairroots-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-stars"></i> Enfants
                        </a>
                        <ul class="dropdown-menu hairroots-dropdown">
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?type=enfant&sub=soins">
                                <i class="bi bi-droplet-fill me-2"></i> Soins de cheveux</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/coiffures.php?type=enfant">
                                <i class="bi bi-scissors me-2"></i> Coiffure</a></li>
                            <li><a class="dropdown-item" href="/ecommerce/products/index.php?type=enfant&sub=meches">
                                <i class="bi bi-bag-fill me-2"></i> Achat des mèches</a></li>
                        </ul>
                    </li>

                    <!-- Nos Coiffeuses -->
                    <li class="nav-item">
                        <a class="nav-link hairroots-link" href="/ecommerce/coiffeuses.php">
                            <i class="bi bi-people-fill"></i> Nos Coiffeuses
                        </a>
                    </li>

                    <!-- Prendre RDV -->
                    <li class="nav-item">
                        <a class="nav-link hairroots-link nav-rdv" href="/ecommerce/rendez-vous.php">
                            Prendre RDV
                        </a>
                    </li>

                </ul>

                <!-- ICONES DROITE -->
                <div class="d-flex align-items-center gap-2">
                    <a href="#" class="nav-icon" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="bi bi-search"></i>
                    </a>
                    <a href="/ecommerce/user/wishlist.php" class="nav-icon">
                        <i class="bi bi-heart"></i>
                    </a>
                    <a href="/ecommerce/cart/index.php" class="nav-icon position-relative">
                        <i class="bi bi-bag"></i>
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a class="nav-icon dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end hairroots-dropdown">
                                <li><span class="dropdown-item-text fw-bold text-brown"> <?php echo $_SESSION['first_name']; ?></span></li>
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
                    <h5 class="mb-3" style="font-family:'Playfair Display',serif;color:#3E1F0D;"> Rechercher un produit</h5>
                    <form action="/ecommerce/products/index.php" method="GET">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control border-0 bg-light" name="search"
                                   placeholder="Mèches bouclées, shampoing, huile...">
                            <button class="btn btn-gold px-4" type="submit">Rechercher</button>
                        </div>
                    </form>
                    <div class="mt-3">
                        <span class="text-muted small">Suggestions : </span>
                        <a href="/ecommerce/products/index.php?category=1" class="badge bg-light text-dark text-decoration-none me-1"> Bouclés</a>
                        <a href="/ecommerce/products/index.php?category=2" class="badge bg-light text-dark text-decoration-none me-1"> Crépus</a>
                        <a href="/ecommerce/products/index.php?category=5" class="badge bg-light text-dark text-decoration-none me-1"> Soins</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<main>