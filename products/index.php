<?php
require_once '../config/database.php';
$page_title = 'Nos Produits - HairRoots';

// Récupérer les catégories
$stmt_categories = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt_categories->fetchAll();

// Filtres
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$price_filter    = isset($_GET['price'])    ? $_GET['price']           : 'all';
$search          = isset($_GET['search'])   ? trim($_GET['search'])    : '';
$type_cheveux    = isset($_GET['type'])     ? $_GET['type']            : '';

// Construction SQL
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.active = 1";
$params = [];

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

include '../includes/header.php';
?>

<div class="container my-5">

    <!-- TITRE PAGE -->
    <div class="page-header text-center mb-5">
        <h1 class="page-title">🛍️ Nos Produits</h1>
        <div class="gold-line mx-auto"></div>
        <p class="page-subtitle">Mèches et soins adaptés à tous les types de cheveux</p>
    </div>

    <!-- FILTRES PAR TYPE DE CHEVEUX -->
    <div class="type-filter-bar mb-4">
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="index.php" class="type-filter-btn <?= $category_filter == 0 ? 'active' : '' ?>">
                🌟 Tous
            </a>
            <a href="index.php?category=1" class="type-filter-btn <?= $category_filter == 1 ? 'active' : '' ?>">
                🌀 Bouclés
            </a>
            <a href="index.php?category=2" class="type-filter-btn <?= $category_filter == 2 ? 'active' : '' ?>">
                ✨ Crépus
            </a>
            <a href="index.php?category=3" class="type-filter-btn <?= $category_filter == 3 ? 'active' : '' ?>">
                💫 Lisses
            </a>
            <a href="index.php?category=4" class="type-filter-btn <?= $category_filter == 4 ? 'active' : '' ?>">
                🌊 Ondulés
            </a>
            <a href="index.php?category=5" class="type-filter-btn <?= $category_filter == 5 ? 'active' : '' ?>">
                🌿 Soins
            </a>
            <a href="index.php?category=6" class="type-filter-btn <?= $category_filter == 6 ? 'active' : '' ?>">
                🧒 Enfants
            </a>
            <a href="index.php?category=7" class="type-filter-btn <?= $category_filter == 7 ? 'active' : '' ?>">
                👨 Hommes
            </a>
        </div>
    </div>

    <!-- FILTRES AVANCÉS -->
    <div class="filter-card mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <!-- Recherche -->
            <div class="col-md-5">
                <label class="filter-label">🔍 Rechercher</label>
                <input type="text" class="form-control filter-input" name="search"
                       placeholder="Mèches, shampoing, huile..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <!-- Catégorie -->
            <div class="col-md-3">
                <label class="filter-label">📂 Catégorie</label>
                <select class="form-select filter-input" name="category">
                    <option value="0">Toutes les catégories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Prix -->
            <div class="col-md-2">
                <label class="filter-label">💰 Prix</label>
                <select class="form-select filter-input" name="price">
                    <option value="all"    <?= $price_filter === 'all'    ? 'selected' : '' ?>>Tous</option>
                    <option value="0-20"   <?= $price_filter === '0-20'   ? 'selected' : '' ?>>0€ - 20€</option>
                    <option value="20-40"  <?= $price_filter === '20-40'  ? 'selected' : '' ?>>20€ - 40€</option>
                    <option value="40-plus"<?= $price_filter === '40-plus'? 'selected' : '' ?>>40€ et +</option>
                </select>
            </div>
            <!-- Boutons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="index.php" class="btn btn-outline-brown">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
        <div class="mt-2">
            <small class="text-muted">
                <i class="bi bi-box-seam"></i> 
                <strong><?= count($products) ?></strong> produit(s) trouvé(s)
            </small>
        </div>
    </div>

    <!-- GRILLE PRODUITS -->
    <?php if(count($products) > 0): ?>
        <div class="row g-4">
            <?php foreach($products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card-hr h-100">
                        <!-- IMAGE -->
                        <div class="product-img-wrap position-relative">
                            <img src="<?= !empty($product['image']) ? htmlspecialchars($product['image']) : 'https://via.placeholder.com/400x300?text=HairRoots' ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="product-img">
                            <?php if($product['featured']): ?>
                                <span class="badge-featured">⭐ Vedette</span>
                            <?php endif; ?>
                            <?php if($product['stock'] <= 10 && $product['stock'] > 0): ?>
                                <span class="badge-stock-low">⚠️ Stock limité</span>
                            <?php endif; ?>
                            <!-- OVERLAY AU SURVOL -->
                            <div class="product-overlay">
                                <a href="detail.php?id=<?= $product['id'] ?>" class="overlay-btn">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                            </div>
                        </div>
                        <!-- INFOS -->
                        <div class="product-body">
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
                                    <button class="btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                        <i class="bi bi-bag-plus"></i> Ajouter
                                    </button>
                                <?php else: ?>
                                    <span class="btn-rupture">Rupture</span>
                                <?php endif; ?>
                            </div>
                            <!-- STOCK -->
                            <div class="mt-2">
                                <?php if($product['stock'] > 10): ?>
                                    <span class="stock-badge stock-ok">✅ <?= $product['stock'] ?> disponibles</span>
                                <?php elseif($product['stock'] > 0): ?>
                                    <span class="stock-badge stock-warn">⚠️ Plus que <?= $product['stock'] ?></span>
                                <?php else: ?>
                                    <span class="stock-badge stock-out">❌ Rupture de stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- AUCUN PRODUIT -->
        <div class="empty-state text-center py-5">
            <div class="empty-icon">🔍</div>
            <h3>Aucun produit trouvé</h3>
            <p class="text-muted">Essayez de modifier vos filtres de recherche</p>
            <a href="index.php" class="btn btn-gold mt-3">
                <i class="bi bi-arrow-left"></i> Voir tous les produits
            </a>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>