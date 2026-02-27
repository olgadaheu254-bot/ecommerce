<?php
require_once '../config/database.php';
$page_title = 'Gestion des Commandes - Admin';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

// Traitement du changement de statut
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if($stmt->execute([$new_status, $order_id])) {
        $_SESSION['success_message'] = "Statut de la commande mis à jour avec succès.";
    }
    header('Location: orders.php');
    exit;
}

// Récupérer toutes les commandes
$stmt = $pdo->query("
    SELECT o.*, u.username, u.first_name, u.last_name, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

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
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box-seam"></i> Produits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
                            <i class="bi bi-cart-check"></i> Commandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/mon-ecommerce/index.php">
                            <i class="bi bi-arrow-left"></i> Retour au site
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-cart-check"></i> Gestion des Commandes</h1>
                <div class="text-muted">
                    <?php echo count($orders); ?> commande(s) au total
                </div>
            </div>

            <!-- Messages -->
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Liste des commandes -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if(count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Paiement</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td><strong><?php echo number_format($order['total_amount'], 2); ?> €</strong></td>
                                            <td>
                                                <?php
                                                $payment_badges = [
                                                    'pending' => 'warning',
                                                    'paid' => 'success',
                                                    'failed' => 'danger'
                                                ];
                                                $payment_labels = [
                                                    'pending' => 'En attente',
                                                    'paid' => 'Payé',
                                                    'failed' => 'Échoué'
                                                ];
                                                $pbadge = $payment_badges[$order['payment_status']] ?? 'secondary';
                                                $plabel = $payment_labels[$order['payment_status']] ?? $order['payment_status'];
                                                ?>
                                                <span class="badge bg-<?php echo $pbadge; ?>"><?php echo $plabel; ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['payment_method']); ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm" 
                                                            onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>En traitement</option>
                                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Livrée</option>
                                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                    <i class="bi bi-eye"></i> Détails
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal pour les détails de la commande -->
                                        <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Commande <?php echo htmlspecialchars($order['order_number']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Informations client</h6>
                                                                <p>
                                                                    <strong>Nom :</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                                                                    <strong>Email :</strong> <?php echo htmlspecialchars($order['email']); ?><br>
                                                                    <strong>Username :</strong> <?php echo htmlspecialchars($order['username']); ?>
                                                                </p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Informations commande</h6>
                                                                <p>
                                                                    <strong>Date :</strong> <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?><br>
                                                                    <strong>Montant :</strong> <?php echo number_format($order['total_amount'], 2); ?> €<br>
                                                                    <strong>Paiement :</strong> <?php echo htmlspecialchars($order['payment_method']); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <h6>Adresse de livraison</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                                        
                                                        <?php if($order['notes']): ?>
                                                            <hr>
                                                            <h6>Notes</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <hr>
                                                        <h6>Articles commandés</h6>
                                                        <?php
                                                        $stmt_items = $pdo->prepare("
                                                            SELECT oi.*, p.name 
                                                            FROM order_items oi
                                                            LEFT JOIN products p ON oi.product_id = p.id
                                                            WHERE oi.order_id = ?
                                                        ");
                                                        $stmt_items->execute([$order['id']]);
                                                        $items = $stmt_items->fetchAll();
                                                        ?>
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Produit</th>
                                                                    <th>Prix</th>
                                                                    <th>Qté</th>
                                                                    <th>Sous-total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach($items as $item): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item['name'] ?? 'Produit supprimé'); ?></td>
                                                                        <td><?php echo number_format($item['price'], 2); ?> €</td>
                                                                        <td><?php echo $item['quantity']; ?></td>
                                                                        <td><strong><?php echo number_format($item['subtotal'], 2); ?> €</strong></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                                <tr>
                                                                    <td colspan="3" class="text-end"><strong>TOTAL :</strong></td>
                                                                    <td><strong class="text-success"><?php echo number_format($order['total_amount'], 2); ?> €</strong></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="mt-3">Aucune commande pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #333;
    padding: 10px 20px;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background-color: #e9ecef;
    color: #0d6efd;
}
</style>

<?php include '../includes/footer.php'; ?>