<?php
require_once '../config/database.php';
$page_title = 'Gestion des Produits - Admin';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

// Récupérer tous les produits
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <h5 class="p-3 mb-0 bg-primary text-white">
                    <i class="bi bi-speedometer2"></i> Admin Panel
                </h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">
                            <i class="bi bi-box-seam"></i> Produits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Commandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ecommerce/index.php">
                            <i class="bi bi-arrow-left"></i> Retour au site
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-box-seam"></i> Gestion des Produits</h1>
                <div>
                    <a href="add-product.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Ajouter un produit
                    </a>
                </div>
                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Liste des produits -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Nom</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/50'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover;" 
                                                 class="rounded">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                        </td>
                                        <td>
                                            <?php if($product['category_name']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Non catégorisé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo number_format($product['price'], 2); ?> €</strong></td>
                                        <td>
                                            <?php if($product['stock'] < 10): ?>
                                                <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                            <?php elseif($product['stock'] < 50): ?>
                                                <span class="badge bg-warning"><?php echo $product['stock']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $product['stock']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($product['active']): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete-product.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')" 
                                                   title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if(count($products) == 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="mt-3">Aucun produit pour le moment.</p>
                            <a href="add-product.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Ajouter le premier produit
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>


<?php include '../includes/footer.php'; ?>