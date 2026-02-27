<?php
require_once '../config/database.php';
$page_title = 'Dashboard Admin - MonShop';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

// Statistiques
$stats = array();

// Nombre total de produits
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$stats['products'] = $stmt->fetch()['total'];

// Nombre total de commandes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
$stats['orders'] = $stmt->fetch()['total'];

// Nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch()['total'];

// Chiffre d'affaires total
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
$stats['revenue'] = $stmt->fetch()['total'] ?? 0;

// Commandes récentes
$stmt = $pdo->query("
    SELECT o.*, u.username, u.first_name, u.last_name 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Produits en rupture de stock
$stmt = $pdo->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
$low_stock = $stmt->fetchAll();

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
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
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
                <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
                <div class="text-muted">
                    Bienvenue, <?php echo htmlspecialchars($_SESSION['first_name']); ?> !
                </div>
            </div>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Produits</h6>
                                    <h2 class="mb-0"><?php echo $stats['products']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-box-seam display-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Commandes</h6>
                                    <h2 class="mb-0"><?php echo $stats['orders']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-cart-check display-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Utilisateurs</h6>
                                    <h2 class="mb-0"><?php echo $stats['users']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-people display-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Revenus</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['revenue'], 0); ?> €</h2>
                                </div>
                                <div>
                                    <i class="bi bi-currency-euro display-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commandes récentes -->
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Commandes récentes</h5>
                        </div>
                        <div class="card-body">
                            <?php if(count($recent_orders) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Client</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recent_orders as $order): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                    <td><strong><?php echo number_format($order['total_amount'], 2); ?> €</strong></td>
                                                    <td>
                                                        <?php
                                                        $badges = [
                                                            'pending' => 'warning',
                                                            'processing' => 'info',
                                                            'shipped' => 'primary',
                                                            'delivered' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $badge = $badges[$order['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($order['status']); ?></span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="orders.php" class="btn btn-primary btn-sm">Voir toutes les commandes</a>
                            <?php else: ?>
                                <p class="text-muted">Aucune commande pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Produits en rupture de stock -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Stock faible</h5>
                        </div>
                        <div class="card-body">
                            <?php if(count($low_stock) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach($low_stock as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?php echo htmlspecialchars($product['name']); ?></span>
                                            <span class="badge bg-danger rounded-pill"><?php echo $product['stock']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="products.php" class="btn btn-danger btn-sm mt-3">Gérer les produits</a>
                            <?php else: ?>
                                <p class="text-muted">Tous les produits sont en stock ✅</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<?php include '../includes/footer.php'; ?>