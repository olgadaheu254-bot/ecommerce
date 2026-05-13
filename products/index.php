<?php
require_once '../config/database.php';
$page_title = 'Nos Produits - HairRoots';

// Recuperer les categories
$stmt_categories = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt_categories->fetchAll();

// Filtres
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$price_filter    = isset($_GET['price'])    ? $_GET['price']          : 'all';
$search          = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$type_filter     = isset($_GET['type'])     ? trim($_GET['type'])     : '';
$sub_filter      = isset($_GET['sub'])      ? trim($_GET['sub'])      : '';

// Construction SQL
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.active = 1";
$params = [];

if(!empty($type_filter)) {
    $sql .= " AND (p.genre = ? OR p.genre = 'tous')";
    $params[] = $type_filter;
}

if($sub_filter === 'soins') {
    $sql .= " AND p.category_id = 5";
} elseif($sub_filter === 'meches') {
    $sql .= " AND p.category_id IN (1,2,3,4)";
}

if($category_filter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if($price_filter === '0-20') {
    $sql .= " AND p.price <= 20";
} elseif($price_filter === '20-40') {
    $sql .= " AND p.price > 20 AND p.price <= 40";
} elseif($price_filter === '40-plus') {
    $sql .= " AND p.price > 40";
}

if(!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.featured DESC, p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Titre dynamique
$titre_page = 'Nos Produits';
if($sub_filter === 'soins')        $titre_page = 'Soins Cheveux';
elseif($sub_filter === 'meches')   $titre_page = 'Nos Meches';
elseif($category_filter == 1)      $titre_page = 'Meches Bouclees';
elseif($category_filter == 2)      $titre_page = 'Meches Crepues';
elseif($category_filter == 3)      $titre_page = 'Meches Lisses';
elseif($category_filter == 4)      $titre_page = 'Meches Ondulees';
elseif($category_filter == 5)      $titre_page = 'Soins Cheveux';

include '../includes/header.php';
?>

<div class="container my-5">

    <!-- TITRE PAGE -->
    <div class="page-header text-center mb-5">
        <h1 class="page-title"><?= htmlspecialchars($titre_page) ?></h1>
        <div class="gold-line mx-auto"></div>
        <p class="page-subtitle">Meches et soins adaptes a tous les types de cheveux</p>
    </div>

    <!-- FILTRES PAR TYPE DE CHEVEUX -->
    <div class="type-filter-bar mb-4">
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="index.php" class="type-filter-btn <?= (!$category_filter && !$sub_filter) ? 'active' : '' ?>">Tous</a>
            <a href="index.php?category=1" class="type-filter-btn <?= $category_filter == 1 ? 'active' : '' ?>">Boucles</a>
            <a href="index.php?category=2" class="type-filter-btn <?= $category_filter == 2 ? 'active' : '' ?>">Crepus</a>
            <a href="index.php?category=3" class="type-filter-btn <?= $category_filter == 3 ? 'active' : '' ?>">Lisses</a>
            <a href="index.php?category=4" class="type-filter-btn <?= $category_filter == 4 ? 'active' : '' ?>">Ondules</a>
            <a href="index.php?category=5" class="type-filter-btn <?= $category_filter == 5 ? 'active' : '' ?>">Soins</a>
        </div>
    </div>

    <!-- FILTRES AVANCES -->
    <div class="filter-card mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="filter-label">Rechercher</label>
                <input type="text" class="form-control filter-input" name="search"
                       placeholder="Meches, shampoing, huile..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="filter-label">Categorie</label>
                <select class="form-select filter-input" name="category">
                    <option value="0">Toutes les categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label">Prix</label>
                <select class="form-select filter-input" name="price">
                    <option value="all"     <?= $price_filter === 'all'     ? 'selected' : '' ?>>Tous</option>
                    <option value="0-20"    <?= $price_filter === '0-20'    ? 'selected' : '' ?>>0 - 20€</option>
                    <option value="20-40"   <?= $price_filter === '20-40'   ? 'selected' : '' ?>>20 - 40€</option>
                    <option value="40-plus" <?= $price_filter === '40-plus' ? 'selected' : '' ?>>40€ et +</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-gold w-100">Filtrer</button>
                <a href="index.php" class="btn btn-outline-brown">X</a>
            </div>
        </form>
        <div class="mt-2">
            <small class="text-muted">
                <strong><?= count($products) ?></strong> produit(s) trouve(s)
            </small>
        </div>
    </div>

    <!-- GRILLE PRODUITS -->
    <?php if(count($products) > 0): ?>
        <div class="scroll-horizontal">
            <?php foreach($products as $product): ?>
                <div class="scroll-item">
                    <div class="product-card-hr h-100">
                        <!-- IMAGE -->
                                                        <div class="product-img-wrap position-relative">
                                    <?php if(!empty($product['image'])): ?>
                                        <img src="/ecommerce/<?= htmlspecialchars($product['image']) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                            class="product-img">
                                    <?php else: ?>
                                        <div style="height:220px;background:linear-gradient(135deg,#F5E6D3,#FDEBD0);display:flex;align-items:center;justify-content:center;font-size:3rem;">
                                        </div>
                                    <?php endif; ?>
                                    <?php if($product['featured']): ?>
                                        <span class="badge-featured">Vedette</span>
                                    <?php endif; ?>
                                    <?php if($product['stock'] <= 10 && $product['stock'] > 0): ?>
                                        <span class="badge-stock-low">Stock limite</span>
                                    <?php endif; ?>
                                  
                                    <div class="product-overlay">
                                        <a href="detail.php?id=<?= $product['id'] ?>" class="overlay-btn">Voir</a>
                                    </div>
                                </div>
                                                        <!-- INFOS -->
                        <div class="product-body">
                                <button class="btn-favori-bottom" style="width:auto" onclick="toggleFavori(this, <?= $product['id'] ?>)">
                                    <i class="bi bi-heart"></i> Favoris
                                </button>
                            <?php if($product['category_name']): ?>
                                <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                            <?php endif; ?>
                            <h6 class="product-name"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="product-desc">
                                <?= substr(htmlspecialchars($product['description'] ?? ''), 0, 80) ?>...
                            </p>
                            <div class="product-footer">
                                <span class="product-price"><?= number_format($product['price'], 2) ?>€</span>
                                <?php if($product['stock'] > 0): ?>
                                    <button class="btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">Ajouter</button>
                                <?php else: ?>
                                    <span class="btn-rupture">Rupture</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-2">
                                <?php if($product['stock'] > 10): ?>
                                    <span class="stock-badge stock-ok"><?= $product['stock'] ?> disponibles</span>
                                <?php elseif($product['stock'] > 0): ?>
                                    <span class="stock-badge stock-warn">Plus que <?= $product['stock'] ?></span>
                                <?php else: ?>
                                    <span class="stock-badge stock-out">Rupture de stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-state text-center py-5">
            <h3>Aucun produit trouve</h3>
            <p class="text-muted">Essayez de modifier vos filtres de recherche</p>
            <a href="index.php" class="btn btn-gold mt-3">Voir tous les produits</a>
        </div>
    <?php endif; ?>

</div>
<div class="toast-prod" id="toast-prod"></div>
<script src="/ecommerce/assets/js/script.js"></script>
<?php include '../includes/footer.php'; ?>